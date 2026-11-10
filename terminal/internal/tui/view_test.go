package tui

import (
	"encoding/base64"
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
		Photos: []site.Photo{{
			Title: "Dock at dusk",
			URL:   "https://cdn.example/dock.jpg",
		}},
		Contact: struct {
			Email     string `json:"email"`
			Phone     string `json:"phone"`
			ResumeURL string `json:"resume_url"`
		}{
			Email:     "Erik@ErikGratz.com",
			Phone:     "937-218-2673",
			ResumeURL: "https://docs.google.com/document/d/resume",
		},
	}
}

func TestPhotoShowsThePublicLink(t *testing.T) {
	screen := wide(NewShell(sampleSnap(), nil))
	screen.section = sectionPhoto
	if !strings.Contains(screen.View(), "https://cdn.example/dock.jpg") {
		t.Fatalf("view: %s", screen.View())
	}
}

func TestEnterOpensMailAndResume(t *testing.T) {
	screen := wide(NewShell(sampleSnap(), nil))
	screen.section = sectionContact
	mail, cmd := screen.Update(tea.KeyMsg{Type: tea.KeyEnter})
	if cmd == nil || !strings.Contains(mail.View(), "mailto:Erik@ErikGratz.com") {
		t.Fatalf("mail: %s", mail.View())
	}
	if !sequenceOpens("mailto:Erik@ErikGratz.com") {
		t.Fatal("mail sequence")
	}
	screen.cursor = 2
	resume, cmd := screen.Update(tea.KeyMsg{Type: tea.KeyEnter})
	if cmd == nil || !strings.Contains(resume.View(), "https://docs.google.com/document/d/resume") {
		t.Fatalf("resume: %s", resume.View())
	}
	if !sequenceOpens("https://docs.google.com/document/d/resume") {
		t.Fatal("resume sequence")
	}
}

func sequenceOpens(target string) bool {
	var buf strings.Builder
	writer := &ttyWrite{seq: openSequence(target)}
	writer.SetStdout(&buf)
	if err := writer.Run(); err != nil {
		return false
	}
	encoded := strings.TrimPrefix(buf.String(), "\x1b]1337;OpenURL=:")
	encoded = strings.TrimSuffix(encoded, "\x07")
	raw, err := base64.StdEncoding.DecodeString(encoded)
	return err == nil && string(raw) == target
}

func TestNotePageIsAContactForm(t *testing.T) {
	api := &recordingAPI{}
	screen := wide(NewShell(sampleSnap(), api))
	screen.section = sectionContact
	screen.cursor = 3
	view := screen.View()
	if !strings.Contains(view, "Name") || !strings.Contains(view, "Email or phone") || !strings.Contains(view, "Message") {
		t.Fatalf("form: %s", view)
	}
	var model tea.Model = screen
	var cmd tea.Cmd
	model, _ = model.Update(tea.KeyMsg{Type: tea.KeyRunes, Runes: []rune("Ada")})
	model, _ = model.Update(tea.KeyMsg{Type: tea.KeyEnter})
	model, _ = model.Update(tea.KeyMsg{Type: tea.KeyRunes, Runes: []rune("ada@example.com")})
	model, _ = model.Update(tea.KeyMsg{Type: tea.KeyEnter})
	model, _ = model.Update(tea.KeyMsg{Type: tea.KeyRunes, Runes: []rune("Hello from the terminal.")})
	model, cmd = model.Update(tea.KeyMsg{Type: tea.KeyEnter})
	if cmd == nil {
		t.Fatal("expected a send")
	}
	model, _ = model.Update(cmd())
	if !strings.Contains(model.View(), "contact request sent!") {
		t.Fatalf("sent: %s", model.View())
	}
	if api.name != "Ada" || api.contact != "ada@example.com" || api.message != "Hello from the terminal." {
		t.Fatalf("note: %+v", api)
	}
}

func TestFitAsksWithBothFieldsAndShowsTheAnswer(t *testing.T) {
	api := &recordingAPI{answer: "Pocketnest is the closest match."}
	screen := wide(NewShell(sampleSnap(), api))
	screen.section = sectionFit
	var model tea.Model = screen
	var cmd tea.Cmd
	model, _ = model.Update(tea.KeyMsg{Type: tea.KeyRunes, Runes: []rune("Go services")})
	model, _ = model.Update(tea.KeyMsg{Type: tea.KeyDown})
	model, _ = model.Update(tea.KeyMsg{Type: tea.KeyRunes, Runes: []rune("How well do I fit?")})
	if !strings.Contains(model.View(), "Job description") || !strings.Contains(model.View(), "Your question") {
		t.Fatalf("fields: %s", model.View())
	}
	model, cmd = model.Update(tea.KeyMsg{Type: tea.KeyEnter})
	if cmd == nil || !strings.Contains(model.View(), "asking") {
		t.Fatalf("asking: %s", model.View())
	}
	model, _ = model.Update(cmd())
	if !strings.Contains(model.View(), "Pocketnest is the closest match.") {
		t.Fatalf("answer: %s", model.View())
	}
	if api.question != "How well do I fit?" || api.job != "Go services" {
		t.Fatalf("asked %q %q", api.question, api.job)
	}
}

func TestFitErrorStaysOnThePage(t *testing.T) {
	screen := wide(NewShell(sampleSnap(), nil))
	screen.section = sectionFit
	next, _ := screen.Update(fitMsg{err: errString("too many requests")})
	if !strings.Contains(next.View(), "too many requests") {
		t.Fatalf("error: %s", next.View())
	}
}

type errString string

func (e errString) Error() string { return string(e) }

type recordingAPI struct {
	question string
	job      string
	name     string
	contact  string
	message  string
	answer   string
}

func (f *recordingAPI) Fit(question, job string) (string, error) {
	f.question = question
	f.job = job
	return f.answer, nil
}

func (f *recordingAPI) Contact(name, contact, message string) (string, error) {
	f.name = name
	f.contact = contact
	f.message = message
	return "contact request sent!", nil
}
