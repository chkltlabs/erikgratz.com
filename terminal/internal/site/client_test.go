package site

import (
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
)

func TestFetchReadsTheSnapshotAndFollowsALocalRedirect(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Header.Get("X-Terminal-Token") != "secret" {
			http.Error(w, "no", http.StatusUnauthorized)
			return
		}
		if r.Host != "erikgratz.com" {
			http.Error(w, "host", http.StatusBadRequest)
			return
		}
		if r.URL.Path == "/api/terminal/snapshot" {
			http.Redirect(w, r, "/api/terminal/live", http.StatusFound)
			return
		}
		w.Header().Set("Content-Type", "application/json")
		_, _ = w.Write([]byte(`{"identity":{"name":"Erik V Gratz"},"experience":[{"company":"Pocketnest"}]}`))
	}))
	defer server.Close()

	snapshot, err := (Client{
		SnapshotURL:  server.URL + "/api/terminal/snapshot",
		SnapshotHost: "erikgratz.com",
		Token:        "secret",
	}).Fetch()
	if err != nil {
		t.Fatal(err)
	}
	if snapshot.Identity.Name != "Erik V Gratz" || snapshot.Experience[0].Company != "Pocketnest" {
		t.Fatalf("snapshot: %+v", snapshot)
	}
}

func TestPublicHostDialsLoopback(t *testing.T) {
	client := Client{SnapshotHost: "erikgratz.com"}
	if got := client.dialAddr("erikgratz.com:443"); got != "127.0.0.1:443" {
		t.Fatalf("dial: %s", got)
	}
	if got := client.dialAddr("127.0.0.1:2080"); got != "127.0.0.1:2080" {
		t.Fatalf("loopback dial: %s", got)
	}
}

func TestRejectedTokenNamesTheCause(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		http.Error(w, "Unauthorized", http.StatusUnauthorized)
	}))
	defer server.Close()

	_, err := (Client{SnapshotURL: server.URL, Token: "secret"}).Fetch()
	if err == nil || !strings.Contains(err.Error(), "TERMINAL_TOKEN") {
		t.Fatal(err)
	}
}

func TestEmptyTokenIsTheVisibleFailure(t *testing.T) {
	_, err := (Client{SnapshotURL: "http://127.0.0.1/api/terminal/snapshot", SnapshotHost: "erikgratz.com"}).Fetch()
	if err == nil || !strings.Contains(err.Error(), "TERMINAL_TOKEN") {
		t.Fatal(err)
	}
}
