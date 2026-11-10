package tui

import (
	"bytes"
	"fmt"
	"io"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"time"

	"erikgratz.com/terminal/internal/site"
	"github.com/creack/pty"
)

type Backend interface {
	Fit(question, job string) (string, error)
	Contact(name, contact, message string) (string, error)
}

type Shell struct {
	Snap     site.Snapshot
	API      Backend
	Problem  string
	BingoPHP string
	Cwd      string
	Notes    []string
	Started  time.Time
	Commands int
	step     string
	draft    contactDraft
}

type contactDraft struct {
	name    string
	contact string
	message string
}

func NewShell(snapshot site.Snapshot, api Backend) *Shell {
	return &Shell{
		Snap:    snapshot,
		API:     api,
		Started: time.Now(),
	}
}

func (s *Shell) Banner() string {
	name := s.Snap.Identity.Name
	if name == "" {
		name = "Erik V Gratz"
	}
	headline := s.Snap.Identity.Headline
	if headline == "" {
		headline = "I build small, useful tools for the web."
	}
	return strings.TrimRight(fmt.Sprintf("%s\n%s\n\n%s\n", name, headline, s.help()), "\n") + "\n"
}

func (s *Shell) Prompt() string {
	place := "~"
	if s.Cwd != "" {
		place = "~/" + s.Cwd
	}
	if s.step != "" {
		return s.step + "> "
	}
	return "erik@" + place + "$ "
}

func (s *Shell) Execute(line string) (string, bool) {
	line = strings.TrimSpace(line)
	if s.step != "" {
		return s.continueContact(line), false
	}
	if line == "" {
		return "", false
	}
	s.Commands++

	switch {
	case line == "help":
		return s.help(), false
	case line == "whoami":
		return s.Snap.Identity.Name + "\n" + s.Snap.Identity.Sub + "\n", false
	case line == "clear":
		return "\f", false
	case line == "exit" || line == "quit":
		return "", true
	case line == "ls":
		return s.list(), false
	case strings.HasPrefix(line, "cd"):
		return s.changeDir(strings.TrimSpace(strings.TrimPrefix(line, "cd"))), false
	case strings.HasPrefix(line, "cat "):
		return s.show(strings.TrimSpace(strings.TrimPrefix(line, "cat"))), false
	case line == "git log" || line == "experience":
		return s.roles(false), false
	case line == "man erik" || line == "man":
		return s.roles(true), false
	case line == "photo" || line == "photos":
		return s.photos(), false
	case line == "click":
		return "NOICE\n", false
	case line == "bingo":
		return s.playBingo(), false
	case strings.HasPrefix(line, "remember"):
		note := strings.TrimSpace(strings.TrimPrefix(line, "remember"))
		if note == "" {
			return "remember what?\n", false
		}
		s.Notes = append(s.Notes, note)
		return "noted. it lasts until this session ends.\n", false
	case line == "recall":
		if len(s.Notes) == 0 {
			return "(nothing remembered)\n", false
		}
		return strings.Join(s.Notes, "\n") + "\n", false
	case line == "rm -rf /":
		return "\n\n\nRestored.\n", false
	case line == "sudo" || strings.HasPrefix(line, "sudo "):
		return s.patent(), false
	case line == "porthole":
		return s.porthole(), false
	case line == "php artisan" || strings.HasPrefix(line, "php artisan "):
		return s.artisan(line), false
	case line == "fit" || strings.HasPrefix(line, "fit "):
		return s.fit(strings.TrimSpace(strings.TrimPrefix(line, "fit"))), false
	case line == "contact" || line == "mail":
		s.step = "name"
		return "Name?\n", false
	default:
		return "syntax error\n", false
	}
}

func (s *Shell) help() string {
	return `commands
  ls, cd, cat          portfolio, experience, photo, contact
  git log              roles
  man erik             roles, with the detail
  fit <question>       same assistant as the website
  photo                titles and tags
  contact              leave a note
  bingo, click         try them
  remember, recall     gone when you disconnect
  porthole             this session
  help, exit
`
}

func (s *Shell) list() string {
	switch s.Cwd {
	case "":
		return "experience\nportfolio\nphoto\ncontact\n"
	case "portfolio":
		var b strings.Builder
		for _, project := range s.Snap.Portfolio {
			fmt.Fprintf(&b, "%s\n", project.Title)
		}
		return b.String()
	case "experience":
		var b strings.Builder
		for _, role := range s.Snap.Experience {
			fmt.Fprintf(&b, "%s — %s\n", role.Company, role.Title)
		}
		return b.String()
	case "photo":
		var b strings.Builder
		for _, photo := range s.Snap.Photos {
			fmt.Fprintf(&b, "%s\n", photo.Title)
		}
		if b.Len() == 0 {
			return "(no photos)\n"
		}
		return b.String()
	case "contact":
		return "email    " + s.Snap.Contact.Email + "\nphone    " + s.Snap.Contact.Phone + "\nresume   " + s.Snap.Contact.ResumeURL + "\n"
	default:
		return "syntax error\n"
	}
}

func (s *Shell) changeDir(target string) string {
	switch target {
	case "", "~", "/":
		s.Cwd = ""
	case "..":
		s.Cwd = ""
	case "experience", "portfolio", "photo", "contact":
		s.Cwd = target
	default:
		return "no such place\n"
	}
	return ""
}

func (s *Shell) show(name string) string {
	needle := strings.ToLower(name)
	for _, project := range s.Snap.Portfolio {
		if strings.ToLower(project.Title) == needle {
			text := project.Text + "\n"
			if project.Link != "" {
				text += project.Link + "\n"
			}
			return text
		}
	}
	for _, role := range s.Snap.Experience {
		if strings.ToLower(role.Company) == needle || strings.ToLower(role.Title) == needle {
			return formatRole(role, true)
		}
	}
	for _, photo := range s.Snap.Photos {
		if strings.ToLower(photo.Title) == needle {
			return photo.Title + "\n" + photo.Description + "\n" + strings.Join(photo.Tags, ", ") + "\n"
		}
	}
	return "no such file\n"
}

func (s *Shell) roles(full bool) string {
	if len(s.Snap.Experience) == 0 {
		return "(no roles)\n"
	}
	var b strings.Builder
	for _, role := range s.Snap.Experience {
		b.WriteString(formatRole(role, full))
		b.WriteString("\n")
	}
	return b.String()
}

func formatRole(role site.Role, full bool) string {
	var b strings.Builder
	fmt.Fprintf(&b, "%s @ %s\n", role.Title, role.Company)
	fmt.Fprintf(&b, "%s  %s\n", role.Timeframe, role.Location)
	if full {
		for _, bullet := range role.Bullets {
			fmt.Fprintf(&b, "  - %s\n", bullet)
		}
		if len(role.Technologies) > 0 {
			fmt.Fprintf(&b, "  %s\n", strings.Join(role.Technologies, ", "))
		}
	}
	return b.String()
}

func (s *Shell) photos() string {
	if len(s.Snap.Photos) == 0 {
		return "(no photos — they live on the website)\n"
	}
	var b strings.Builder
	for _, photo := range s.Snap.Photos {
		fmt.Fprintf(&b, "%s\n", photo.Title)
		if photo.Description != "" {
			fmt.Fprintf(&b, "  %s\n", photo.Description)
		}
		if len(photo.Tags) > 0 {
			fmt.Fprintf(&b, "  %s\n", strings.Join(photo.Tags, ", "))
		}
	}
	b.WriteString("full frames stay on the website\n")
	return b.String()
}

func (s *Shell) patent() string {
	for _, role := range s.Snap.Experience {
		for _, bullet := range role.Bullets {
			if strings.Contains(strings.ToLower(bullet), "patent") {
				return bullet + "\n"
			}
		}
	}
	return "named on the original Pocketnest patent\n"
}

func (s *Shell) porthole() string {
	return fmt.Sprintf("session   1\nuptime    %s\ncwd       %s\ncommands  %d\n",
		time.Since(s.Started).Round(time.Second),
		s.PromptPlace(),
		s.Commands,
	)
}

func (s *Shell) PromptPlace() string {
	if s.Cwd == "" {
		return "~"
	}
	return "~/" + s.Cwd
}

func (s *Shell) fit(question string) string {
	if question == "" {
		return "fit needs a question\n"
	}
	if s.API == nil {
		return "fit is offline\n"
	}
	answer, err := s.API.Fit(question, "")
	if err != nil {
		return err.Error() + "\n"
	}
	return answer + "\n"
}

func (s *Shell) continueContact(line string) string {
	switch s.step {
	case "name":
		if line == "" {
			return "Name?\n"
		}
		s.draft.name = line
		s.step = "contact"
		return "Email or phone?\n"
	case "contact":
		if line == "" {
			return "Email or phone?\n"
		}
		s.draft.contact = line
		s.step = "message"
		return "Message?\n"
	case "message":
		if line == "" {
			return "Message?\n"
		}
		s.draft.message = line
		s.step = ""
		if s.API == nil {
			return "contact is offline\n"
		}
		message, err := s.API.Contact(s.draft.name, s.draft.contact, s.draft.message)
		s.draft = contactDraft{}
		if err != nil {
			return err.Error() + "\n"
		}
		return message + "\n"
	default:
		s.step = ""
		return "syntax error\n"
	}
}

func (s *Shell) artisan(line string) string {
	fields := strings.Fields(line)
	args := []string{}
	if len(fields) > 2 {
		args = fields[2:]
	}
	if len(args) == 0 {
		return "Available commands:\n  inspire\n  fit\n  bingo:play\n  migrate\n"
	}
	if args[0] == "migrate" {
		return "production is not a playground.\n"
	}
	if args[0] == "inspire" {
		return "I build small, useful tools for the web.\n"
	}
	if args[0] == "bingo:play" {
		return s.playBingo()
	}
	return "command not found\n"
}

func (s *Shell) playBingo() string {
	script := locateBingo(s.BingoPHP)
	if script == "" {
		return "auto-bingo is not installed\n"
	}
	phpBin, err := exec.LookPath("php")
	if err != nil {
		return "php is not installed\n"
	}
	text, err := runAutoBingo(phpBin, script)
	if err != nil {
		if text != "" {
			return text
		}
		return err.Error() + "\n"
	}
	if !strings.HasSuffix(text, "\n") {
		text += "\n"
	}
	return text
}

func locateBingo(explicit string) string {
	candidates := []string{}
	if explicit != "" {
		candidates = append(candidates, explicit)
	}
	if env := os.Getenv("BINGO_PHP"); env != "" {
		candidates = append(candidates, env)
	}
	candidates = append(candidates,
		filepath.Join("terminal", "bingo", "go.php"),
		filepath.Join("bingo", "go.php"),
		filepath.Join("..", "..", "bingo", "go.php"),
	)
	if exe, err := os.Executable(); err == nil {
		candidates = append(candidates, filepath.Join(filepath.Dir(exe), "..", "bingo", "go.php"))
	}
	for _, candidate := range candidates {
		info, err := os.Stat(candidate)
		if err == nil && !info.IsDir() {
			return candidate
		}
	}
	return ""
}

func runAutoBingo(phpBin, script string) (string, error) {
	absolute, err := filepath.Abs(script)
	if err != nil {
		return "", err
	}
	command := exec.Command(phpBin, absolute)
	command.Dir = filepath.Dir(absolute)
	terminal, err := pty.Start(command)
	if err != nil {
		return "", err
	}
	defer terminal.Close()
	if _, err := terminal.Write([]byte("Y\n1\n1\n")); err != nil {
		return "", err
	}
	finished := make(chan struct{})
	var output bytes.Buffer
	go func() {
		_, _ = io.Copy(&output, terminal)
		close(finished)
	}()
	select {
	case <-finished:
	case <-time.After(20 * time.Second):
		if command.Process != nil {
			_ = command.Process.Kill()
		}
		<-finished
		return output.String(), fmt.Errorf("auto-bingo timed out")
	}
	waitErr := command.Wait()
	text := output.String()
	if waitErr != nil && !strings.Contains(text, "BINGO") {
		return text, waitErr
	}
	return text, nil
}
