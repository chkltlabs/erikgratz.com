package envfile

import (
	"os"
	"path/filepath"
	"testing"
)

func TestTokenReadsTheAssignment(t *testing.T) {
	path := filepath.Join(t.TempDir(), "env")
	body := "# comment\n\nTERMINAL_TOKEN=abc123\nOTHER=no\n"
	if err := os.WriteFile(path, []byte(body), 0o600); err != nil {
		t.Fatal(err)
	}
	if got := Token(path); got != "abc123" {
		t.Fatalf("token: %q", got)
	}
}

func TestTokenMissingFileIsEmpty(t *testing.T) {
	if got := Token(filepath.Join(t.TempDir(), "missing")); got != "" {
		t.Fatalf("token: %q", got)
	}
}
