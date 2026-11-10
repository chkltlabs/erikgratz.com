package tui

import (
	"strings"
	"testing"

	"erikgratz.com/terminal/internal/site"
)

func TestClickAndUnknown(t *testing.T) {
	shell := NewShell(site.Snapshot{}, nil)
	got, quit := shell.Execute("click")
	if quit || got != "NOICE\n" {
		t.Fatalf("click: %q quit=%v", got, quit)
	}
	got, _ = shell.Execute("dance")
	if got != "syntax error\n" {
		t.Fatalf("unknown: %q", got)
	}
}

func TestSudoPrintsThePatentBullet(t *testing.T) {
	shell := NewShell(site.Snapshot{
		Experience: []site.Role{{
			Company: "Pocketnest",
			Bullets: []string{"Named on original Patent for Pocketnest"},
		}},
	}, nil)
	got, _ := shell.Execute("sudo")
	if !strings.Contains(got, "Patent") {
		t.Fatalf("sudo: %q", got)
	}
}

func TestGitLogUsesTheSnapshot(t *testing.T) {
	shell := NewShell(site.Snapshot{
		Experience: []site.Role{{
			Company:   "Pocketnest",
			Title:     "Senior Software Engineer, Backend",
			Timeframe: "2022-2026",
			Location:  "Detroit",
		}},
	}, nil)
	got, _ := shell.Execute("git log")
	if !strings.Contains(got, "Pocketnest") || !strings.Contains(got, "2022-2026") {
		t.Fatalf("git log: %q", got)
	}
}

func TestRememberDiesAsTextInTheSession(t *testing.T) {
	shell := NewShell(site.Snapshot{}, nil)
	shell.Execute("remember the deploy port")
	got, _ := shell.Execute("recall")
	if !strings.Contains(got, "the deploy port") {
		t.Fatalf("recall: %q", got)
	}
}

func TestArtisanMigrateDeclines(t *testing.T) {
	shell := NewShell(site.Snapshot{}, nil)
	got, _ := shell.Execute("php artisan migrate")
	if !strings.Contains(got, "not a playground") {
		t.Fatalf("migrate: %q", got)
	}
}

func TestRmRestores(t *testing.T) {
	shell := NewShell(site.Snapshot{}, nil)
	got, _ := shell.Execute("rm -rf /")
	if !strings.Contains(got, "Restored") {
		t.Fatalf("rm: %q", got)
	}
}

func TestBingoFinishes(t *testing.T) {
	shell := NewShell(site.Snapshot{}, nil)
	got, _ := shell.Execute("bingo")
	if !strings.Contains(got, "BINGO") || !strings.Contains(got, "b, i, n, g, o") {
		t.Fatalf("bingo: %q", got)
	}
}

type fakeAPI struct {
	answer  string
	message string
}

func (f fakeAPI) Fit(string, string) (string, error) { return f.answer, nil }
func (f fakeAPI) Contact(string, string, string) (string, error) {
	return f.message, nil
}

func TestContactWalksTheForm(t *testing.T) {
	shell := NewShell(site.Snapshot{}, fakeAPI{message: "contact request sent!"})
	shell.Execute("contact")
	shell.Execute("Ada")
	shell.Execute("ada@example.com")
	got, _ := shell.Execute("Hello from the terminal.")
	if got != "contact request sent!\n" {
		t.Fatalf("contact: %q", got)
	}
}
