package tui

import (
	"bytes"
	"io"
	"testing"

	"erikgratz.com/terminal/internal/site"
)

func TestServeIOAnswersClick(t *testing.T) {
	input, stdin := io.Pipe()
	stdout, output := io.Pipe()
	done := make(chan struct{})
	go func() {
		defer close(done)
		ServeIO(input, output, NewShell(site.Snapshot{}, nil))
		_ = output.Close()
	}()

	var got bytes.Buffer
	copied := make(chan struct{})
	go func() {
		_, _ = io.Copy(&got, stdout)
		close(copied)
	}()

	if _, err := io.WriteString(stdin, "click\n"); err != nil {
		t.Fatal(err)
	}
	_ = stdin.Close()
	<-done
	<-copied
	if !bytes.Contains(got.Bytes(), []byte("NOICE")) {
		t.Fatalf("output: %q", got.String())
	}
}
