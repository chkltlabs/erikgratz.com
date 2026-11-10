package envfile

import (
	"os"
	"strings"
)

func FirstToken(paths ...string) string {
	for _, path := range paths {
		if token := Token(path); token != "" {
			return token
		}
	}
	return ""
}

func Token(path string) string {
	raw, err := os.ReadFile(path)
	if err != nil {
		return ""
	}
	for _, line := range strings.Split(string(raw), "\n") {
		line = strings.TrimSpace(line)
		if line == "" || strings.HasPrefix(line, "#") {
			continue
		}
		key, value, ok := strings.Cut(line, "=")
		if ok && key == "TERMINAL_TOKEN" {
			return strings.Trim(value, `"'`)
		}
	}
	return ""
}
