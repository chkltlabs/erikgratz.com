package site

import (
	"bytes"
	"context"
	"crypto/tls"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net"
	"net/http"
	"net/url"
	"os"
	"strings"
	"time"
)

type Snapshot struct {
	Identity struct {
		Name     string `json:"name"`
		Headline string `json:"headline"`
		Sub      string `json:"sub"`
	} `json:"identity"`
	Experience []Role    `json:"experience"`
	Portfolio  []Project `json:"portfolio"`
	Photos     []Photo   `json:"photos"`
	Contact    struct {
		Email     string `json:"email"`
		Phone     string `json:"phone"`
		ResumeURL string `json:"resume_url"`
	} `json:"contact"`
}

type Role struct {
	Company      string   `json:"company"`
	Location     string   `json:"location"`
	Title        string   `json:"title"`
	Timeframe    string   `json:"timeframe"`
	Bullets      []string `json:"bullets"`
	Technologies []string `json:"technologies"`
}

type Project struct {
	Title string `json:"title"`
	Text  string `json:"text"`
	Link  string `json:"link"`
}

type Photo struct {
	Title       string   `json:"title"`
	Description string   `json:"description"`
	Tags        []string `json:"tags"`
	URL         string   `json:"url"`
}

type Client struct {
	SnapshotURL  string
	SnapshotHost string
	Token        string
	HTTP         *http.Client
}

func LoadFile(path string) (Snapshot, error) {
	raw, err := os.ReadFile(path)
	if err != nil {
		return Snapshot{}, err
	}
	var snapshot Snapshot
	if err := json.Unmarshal(raw, &snapshot); err != nil {
		return Snapshot{}, err
	}
	return snapshot, nil
}

func (c Client) Fetch() (Snapshot, error) {
	if strings.TrimSpace(c.Token) == "" {
		return Snapshot{}, errors.New("TERMINAL_TOKEN is empty, so the site refused the snapshot")
	}
	body, err := c.do(http.MethodGet, c.SnapshotURL, nil, false)
	if err != nil {
		return Snapshot{}, err
	}
	var snapshot Snapshot
	if err := json.Unmarshal(body, &snapshot); err != nil {
		return Snapshot{}, err
	}
	return snapshot, nil
}

func (c Client) Fit(question, job string) (string, error) {
	payload := map[string]string{"question": question}
	if job != "" {
		payload["job_description"] = job
	}
	body, err := c.post(c.endpoint("/fit"), payload)
	if err != nil {
		return "", err
	}
	var parsed struct {
		Answer  string `json:"answer"`
		Message string `json:"message"`
	}
	if err := json.Unmarshal(body, &parsed); err != nil {
		return "", err
	}
	if parsed.Answer == "" {
		return parsed.Message, nil
	}
	return parsed.Answer, nil
}

func (c Client) Contact(name, contact, message string) (string, error) {
	body, err := c.post(c.endpoint("/contact"), map[string]string{
		"name":    name,
		"contact": contact,
		"message": message,
	})
	if err != nil {
		return "", err
	}
	var parsed struct {
		Message string `json:"message"`
	}
	if err := json.Unmarshal(body, &parsed); err != nil {
		return "", err
	}
	if parsed.Message == "" {
		return "contact request sent!", nil
	}
	return parsed.Message, nil
}

func (c Client) endpoint(suffix string) string {
	base := c.SnapshotURL
	if len(base) >= len("/snapshot") && base[len(base)-len("/snapshot"):] == "/snapshot" {
		return base[:len(base)-len("/snapshot")] + suffix
	}
	return base + suffix
}

func (c Client) post(url string, payload any) ([]byte, error) {
	raw, err := json.Marshal(payload)
	if err != nil {
		return nil, err
	}
	return c.do(http.MethodPost, url, raw, false)
}

func (c Client) httpClient() *http.Client {
	if c.HTTP != nil {
		return c.HTTP
	}
	transport := http.DefaultTransport.(*http.Transport).Clone()
	if c.SnapshotHost != "" {
		transport.TLSClientConfig = &tls.Config{
			MinVersion: tls.VersionTLS12,
			ServerName: c.SnapshotHost,
		}
	}
	dialer := &net.Dialer{Timeout: 10 * time.Second}
	transport.DialContext = func(ctx context.Context, network, addr string) (net.Conn, error) {
		return dialer.DialContext(ctx, network, c.dialAddr(addr))
	}
	return &http.Client{
		Timeout:   30 * time.Second,
		Transport: transport,
		CheckRedirect: func(*http.Request, []*http.Request) error {
			return http.ErrUseLastResponse
		},
	}
}

// dialAddr keeps a request for the public site on this machine. nginx on
// 127.0.0.1 presents the certificate for SnapshotHost.
func (c Client) dialAddr(addr string) string {
	host, port, err := net.SplitHostPort(addr)
	if err != nil || c.SnapshotHost == "" || !strings.EqualFold(host, c.SnapshotHost) {
		return addr
	}
	return net.JoinHostPort("127.0.0.1", port)
}

func (c Client) do(method, rawURL string, body []byte, followed bool) ([]byte, error) {
	httpClient := c.httpClient()
	var reader io.Reader
	if body != nil {
		reader = bytes.NewReader(body)
	}
	request, err := http.NewRequest(method, rawURL, reader)
	if err != nil {
		return nil, err
	}
	if c.SnapshotHost != "" {
		request.Host = c.SnapshotHost
	}
	if body != nil {
		request.Header.Set("Content-Type", "application/json")
	}
	request.Header.Set("Accept", "application/json")
	request.Header.Set("X-Terminal-Token", c.Token)

	response, err := httpClient.Do(request)
	if err != nil {
		return nil, err
	}
	defer response.Body.Close()
	responseBody, err := io.ReadAll(io.LimitReader(response.Body, 1<<20))
	if err != nil {
		return nil, err
	}
	if response.StatusCode == http.StatusUnauthorized {
		return nil, errors.New("the site rejected the terminal token; set TERMINAL_TOKEN in the site .env and run php artisan config:cache")
	}
	if response.StatusCode >= 300 && response.StatusCode < 400 && !followed {
		next, ok := c.replay(rawURL, response)
		if ok {
			return c.do(method, next, body, true)
		}
	}
	if response.StatusCode >= 300 {
		var parsed struct {
			Message string `json:"message"`
		}
		_ = json.Unmarshal(responseBody, &parsed)
		if parsed.Message == "" {
			parsed.Message = response.Status
		}
		return nil, fmt.Errorf("%s", parsed.Message)
	}
	return responseBody, nil
}

func (c Client) replay(rawURL string, response *http.Response) (string, bool) {
	location, err := response.Location()
	if err != nil {
		return "", false
	}
	original, err := url.Parse(rawURL)
	if err != nil {
		return "", false
	}
	host := strings.ToLower(location.Hostname())
	same := strings.EqualFold(host, original.Hostname())
	if ip := net.ParseIP(host); ip != nil && ip.IsLoopback() {
		same = true
	}
	if c.SnapshotHost != "" && (strings.EqualFold(host, c.SnapshotHost) || strings.HasSuffix(host, "."+strings.ToLower(c.SnapshotHost))) {
		same = true
		location.Scheme = "https"
		location.Host = c.SnapshotHost
	}
	if !same {
		return "", false
	}
	return location.String(), true
}
