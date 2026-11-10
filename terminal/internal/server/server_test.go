package server

import (
	"bytes"
	"io"
	"net"
	"os"
	"path/filepath"
	"testing"

	gossh "golang.org/x/crypto/ssh"
)

func TestEmptyUsernameGetsTheShell(t *testing.T) {
	dir := t.TempDir()
	snapshot := filepath.Join(dir, "snapshot.json")
	if err := os.WriteFile(snapshot, []byte(`{"identity":{"name":"Erik V Gratz","headline":"tools"}}`), 0o600); err != nil {
		t.Fatal(err)
	}
	listener := listen(t)
	terminal, err := New(Config{
		Listen:       listener.Addr().String(),
		HostKeyPath:  filepath.Join(dir, "host"),
		SnapshotFile: snapshot,
	})
	if err != nil {
		t.Fatal(err)
	}
	go func() { _ = terminal.Serve(listener) }()

	hostKey := terminal.HostSigners[0].PublicKey()
	visitor := dial(t, listener.Addr().String(), "", hostKey)
	shell, err := visitor.NewSession()
	if err != nil {
		t.Fatal(err)
	}
	stdout, err := shell.StdoutPipe()
	if err != nil {
		t.Fatal(err)
	}
	stdin, err := shell.StdinPipe()
	if err != nil {
		t.Fatal(err)
	}
	if err := shell.Shell(); err != nil {
		t.Fatal(err)
	}
	banner := readSome(t, stdout)
	if !bytes.Contains(banner, []byte("Erik V Gratz")) {
		t.Fatalf("banner: %q", banner)
	}
	if _, err := io.WriteString(stdin, "click\n"); err != nil {
		t.Fatal(err)
	}
	clicked := readSome(t, stdout)
	if !bytes.Contains(clicked, []byte("NOICE")) {
		t.Fatalf("click: %q", clicked)
	}
}

func listen(t *testing.T) net.Listener {
	t.Helper()
	listener, err := net.Listen("tcp", "127.0.0.1:0")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = listener.Close() })
	return listener
}

func dial(t *testing.T, addr, user string, host gossh.PublicKey) *gossh.Client {
	t.Helper()
	client, err := gossh.Dial("tcp", addr, &gossh.ClientConfig{
		User:            user,
		HostKeyCallback: gossh.FixedHostKey(host),
	})
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = client.Close() })
	return client
}

func readSome(t *testing.T, reader io.Reader) []byte {
	t.Helper()
	buf := make([]byte, 8192)
	n, err := reader.Read(buf)
	if err != nil && n == 0 {
		t.Fatal(err)
	}
	return buf[:n]
}
