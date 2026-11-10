package tui

import (
	"io"
	"strings"
	"testing"

	"erikgratz.com/terminal/internal/site"
	tea "github.com/charmbracelet/bubbletea"
)

func TestExperienceShowsTheRole(t *testing.T) {
	screen := wide(NewShell(sampleSnap(), nil))
	screen.section = sectionExperience
	view := screen.View()
	if !strings.Contains(view, "Pocketnest") || !strings.Contains(view, "Named on original Patent") {
		t.Fatalf("view: %s", view)
	}
}

func TestSnapshotProblemIsVisible(t *testing.T) {
	shell := NewShell(site.Snapshot{}, nil)
	shell.Problem = "TERMINAL_TOKEN is empty, so the site refused the snapshot"
	view := wide(shell).View()
	if !strings.Contains(view, "TERMINAL_TOKEN") {
		t.Fatalf("view: %s", view)
	}
	next, _ := wide(shell).Update(tea.KeyMsg{Type: tea.KeyRight})
	if !strings.Contains(next.View(), "TERMINAL_TOKEN") {
		t.Fatalf("experience: %s", next.View())
	}
}

func TestRightArrowOpensExperience(t *testing.T) {
	next, _ := wide(NewShell(sampleSnap(), nil)).Update(tea.KeyMsg{Type: tea.KeyRight})
	if !strings.Contains(next.View(), "Pocketnest") || !strings.Contains(next.View(), "Senior Software Engineer") {
		t.Fatalf("view: %s", next.View())
	}
}

func TestBingoFillsTheDetail(t *testing.T) {
	next, _ := wide(NewShell(sampleSnap(), nil)).Update(tea.KeyMsg{Type: tea.KeyRunes, Runes: []rune{'b'}})
	if !strings.Contains(next.View(), "BINGO") {
		t.Fatalf("view: %s", next.View())
	}
}

func wide(shell *Shell) screenModel {
	screen := newScreen(shell, io.Discard)
	screen.width = 120
	screen.height = 36
	return screen
}

func sampleSnap() site.Snapshot {
	return site.Snapshot{
		Identity: struct {
			Name     string `json:"name"`
			Headline string `json:"headline"`
			Sub      string `json:"sub"`
		}{Name: "Erik V Gratz", Headline: "I build small, useful tools for the web.", Sub: "computers"},
		Experience: []site.Role{{
			Company:   "Pocketnest",
			Title:     "Senior Software Engineer, Backend",
			Location:  "Detroit",
			Timeframe: "2022-2026",
			Bullets:   []string{"Named on original Patent for Pocketnest"},
		}},
		Portfolio: []site.Project{{Title: "Laravel Porthole", Text: "A window into the framework."}},
		Contact: struct {
			Email     string `json:"email"`
			Phone     string `json:"phone"`
			ResumeURL string `json:"resume_url"`
		}{Email: "Erik@ErikGratz.com", Phone: "937-218-2673"},
	}
}
