package tui

import (
	"errors"
	"fmt"
	"io"
	"strings"

	tea "github.com/charmbracelet/bubbletea"
	"github.com/charmbracelet/lipgloss"
	"github.com/muesli/termenv"
)

const (
	colorPurple     = "55"
	colorPurpleText = "141"
	colorOnPurple   = "231"
	colorDim        = "245"
	colorText       = "252"
	colorLine       = "240"
)

type section int

const (
	sectionHome section = iota
	sectionExperience
	sectionPortfolio
	sectionPhoto
	sectionContact
	sectionFit
)

var sectionNames = []string{"home", "experience", "portfolio", "photo", "contact", "fit"}

type screenModel struct {
	shell    *Shell
	renderer *lipgloss.Renderer
	width    int
	height   int
	section  section
	cursor   int
	overlay  string
	notice   string
	step     int
	entry    string
	draft    contactDraft
	fitText  string
	fitReply string
	busy     bool
}

func newScreen(shell *Shell, out io.Writer) screenModel {
	renderer := lipgloss.NewRenderer(out)
	renderer.SetColorProfile(termenv.ANSI256)
	return screenModel{
		shell:    shell,
		renderer: renderer,
		width:    80,
		height:   24,
	}
}

func (m screenModel) Init() tea.Cmd { return nil }

func (m screenModel) Update(msg tea.Msg) (tea.Model, tea.Cmd) {
	switch msg := msg.(type) {
	case tea.WindowSizeMsg:
		m.width = msg.Width
		m.height = msg.Height
	case fitMsg:
		m.busy = false
		if msg.err != nil {
			m.notice = msg.err.Error()
			return m, nil
		}
		m.fitReply = msg.answer
		m.notice = ""
	case noteMsg:
		m.notice = string(msg)
		m.step = 0
		m.entry = ""
		m.draft = contactDraft{}
	case tea.KeyMsg:
		return m.key(msg)
	}
	return m, nil
}

func (m screenModel) key(msg tea.KeyMsg) (tea.Model, tea.Cmd) {
	if msg.Type == tea.KeyCtrlC || msg.Type == tea.KeyCtrlD {
		return m, tea.Quit
	}
	if m.overlay != "" {
		m.overlay = ""
		return m, nil
	}
	if m.capturing() {
		return m.typeKey(msg)
	}
	switch msg.Type {
	case tea.KeyLeft:
		m = m.bump(-1)
	case tea.KeyRight, tea.KeyTab:
		m = m.bump(1)
	case tea.KeyUp:
		m = m.move(-1)
	case tea.KeyDown:
		m = m.move(1)
	case tea.KeyEnter:
		return m.activate()
	case tea.KeyRunes:
		return m.runeKey(string(msg.Runes))
	}
	return m, nil
}

func (m screenModel) capturing() bool {
	return m.section == sectionFit || m.step > 0
}

func (m screenModel) typeKey(msg tea.KeyMsg) (tea.Model, tea.Cmd) {
	switch msg.Type {
	case tea.KeyLeft:
		m = m.bump(-1)
	case tea.KeyRight, tea.KeyTab:
		m = m.bump(1)
	case tea.KeyEsc:
		m.step = 0
		m.entry = ""
		m.fitText = ""
	case tea.KeyEnter:
		return m.submit()
	case tea.KeyBackspace:
		m.entry = dropLast(m.entry)
		if m.section == sectionFit {
			m.fitText = dropLast(m.fitText)
		}
	case tea.KeySpace:
		m = m.append(" ")
	case tea.KeyRunes:
		m = m.append(string(msg.Runes))
	}
	return m, nil
}

func (m screenModel) runeKey(value string) (tea.Model, tea.Cmd) {
	switch value {
	case "q":
		return m, tea.Quit
	case "b":
		text, _ := m.shell.Execute("bingo")
		m.overlay = text
	case "h":
		m = m.bump(-1)
	case "l":
		m = m.bump(1)
	case "k":
		m = m.move(-1)
	case "j":
		m = m.move(1)
	case "1", "2", "3", "4", "5", "6":
		m.section = section(value[0] - '1')
		m.cursor = 0
		m.step = 0
	}
	return m, nil
}

func (m screenModel) bump(delta int) screenModel {
	count := len(sectionNames)
	m.section = section((int(m.section) + delta + count) % count)
	m.cursor = 0
	m.step = 0
	m.entry = ""
	m.overlay = ""
	return m
}

func (m screenModel) move(delta int) screenModel {
	items := m.items()
	if len(items) == 0 {
		return m
	}
	m.cursor += delta
	if m.cursor < 0 {
		m.cursor = 0
	}
	if m.cursor >= len(items) {
		m.cursor = len(items) - 1
	}
	return m
}

func (m screenModel) append(value string) screenModel {
	if m.section == sectionFit {
		m.fitText += value
		return m
	}
	m.entry += value
	return m
}

func (m screenModel) activate() (tea.Model, tea.Cmd) {
	if m.section == sectionContact && m.cursor == 3 {
		m.step = 1
		m.entry = ""
	}
	return m, nil
}

func (m screenModel) submit() (tea.Model, tea.Cmd) {
	if m.section == sectionFit {
		question := strings.TrimSpace(m.fitText)
		if question == "" || m.busy {
			return m, nil
		}
		m.busy = true
		m.notice = "asking…"
		return m, askFit(m.shell.API, question)
	}
	line := strings.TrimSpace(m.entry)
	if line == "" {
		return m, nil
	}
	switch m.step {
	case 1:
		m.draft.name = line
		m.step = 2
		m.entry = ""
	case 2:
		m.draft.contact = line
		m.step = 3
		m.entry = ""
	case 3:
		name, contact, message := m.draft.name, m.draft.contact, line
		m.step = 0
		m.entry = ""
		return m, sendNote(m.shell.API, name, contact, message)
	}
	return m, nil
}

func askFit(api Backend, question string) tea.Cmd {
	return func() tea.Msg {
		if api == nil {
			return fitMsg{err: errors.New("fit is offline")}
		}
		answer, err := api.Fit(question, "")
		return fitMsg{answer: answer, err: err}
	}
}

func sendNote(api Backend, name, contact, message string) tea.Cmd {
	return func() tea.Msg {
		if api == nil {
			return noteMsg("contact is offline")
		}
		text, err := api.Contact(name, contact, message)
		if err != nil {
			return noteMsg(err.Error())
		}
		return noteMsg(text)
	}
}

type fitMsg struct {
	answer string
	err    error
}

type noteMsg string

func (m screenModel) View() string {
	if m.width < 48 || m.height < 12 {
		return "Make the terminal a little wider.\n"
	}
	bodyHeight := m.height - lipgloss.Height(m.tabs()) - 2
	if bodyHeight < 6 {
		bodyHeight = 6
	}
	leftWidth := 32
	if leftWidth > m.width/2 {
		leftWidth = m.width / 3
	}
	box := m.box()
	frameW := box.GetHorizontalFrameSize()
	frameH := box.GetVerticalFrameSize()
	leftInner := leftWidth - frameW
	if leftInner < 16 {
		leftInner = 16
	}
	left := box.Width(leftInner).Height(bodyHeight - frameH).Render(m.list(leftInner))
	rightInner := m.width - lipgloss.Width(left) - frameW
	if rightInner < 20 {
		rightInner = 20
	}
	right := box.Width(rightInner).Height(bodyHeight - frameH).Render(m.detail(rightInner))
	return lipgloss.JoinVertical(lipgloss.Left,
		m.tabs(),
		m.rule(),
		lipgloss.JoinHorizontal(lipgloss.Top, left, right),
		m.footer(),
	)
}

func (m screenModel) box() lipgloss.Style {
	return m.renderer.NewStyle().
		Border(lipgloss.NormalBorder()).
		BorderForeground(lipgloss.Color(colorLine)).
		Padding(0, 1)
}

func (m screenModel) tabs() string {
	boxed := m.boxedTabs()
	if lipgloss.Width(boxed) <= m.width {
		return boxed
	}
	return m.compactTabs()
}

func (m screenModel) compactTabs() string {
	gap := m.renderer.NewStyle().Foreground(lipgloss.Color(colorLine)).Render(" │ ")
	parts := make([]string, len(sectionNames))
	for i, name := range sectionNames {
		label := fmt.Sprintf("%d %s", i+1, name)
		style := m.renderer.NewStyle().Foreground(lipgloss.Color(colorText))
		if section(i) == m.section {
			style = style.Foreground(lipgloss.Color(colorOnPurple)).Background(lipgloss.Color(colorPurple)).Bold(true)
		}
		parts[i] = style.Render(" " + label + " ")
	}
	brand := m.renderer.NewStyle().Foreground(lipgloss.Color(colorPurpleText)).Bold(true).Render("erikgratz.com")
	return brand + gap + strings.Join(parts, gap)
}

func (m screenModel) boxedTabs() string {
	brand := m.renderer.NewStyle().
		Border(lipgloss.NormalBorder()).
		BorderForeground(lipgloss.Color(colorLine)).
		Padding(0, 1).
		Foreground(lipgloss.Color(colorPurpleText)).
		Bold(true).
		Render("erikgratz.com")
	parts := []string{brand}
	for i, name := range sectionNames {
		style := m.renderer.NewStyle().
			Border(lipgloss.NormalBorder()).
			BorderForeground(lipgloss.Color(colorLine)).
			Padding(0, 1).
			Foreground(lipgloss.Color(colorText))
		if section(i) == m.section {
			style = style.
				Foreground(lipgloss.Color(colorOnPurple)).
				Background(lipgloss.Color(colorPurple)).
				BorderForeground(lipgloss.Color(colorPurple)).
				Bold(true)
		}
		parts = append(parts, style.Render(fmt.Sprintf("%d %s", i+1, name)))
	}
	return lipgloss.JoinHorizontal(lipgloss.Bottom, parts...)
}

func (m screenModel) rule() string {
	return m.renderer.NewStyle().Foreground(lipgloss.Color(colorLine)).Render(strings.Repeat("─", m.width))
}

func (m screenModel) footer() string {
	hint := "←/→ sections    ↑/↓ items    1-6 jump    q quit    b bingo"
	switch {
	case m.section == sectionFit:
		hint = "type a question    enter ask    esc clear    ctrl-c quit"
	case m.step > 0:
		hint = "enter continues    esc cancel    ctrl-c quit"
	case m.notice != "":
		hint = m.notice
	}
	return m.renderer.NewStyle().Foreground(lipgloss.Color(colorDim)).Render(hint)
}

func (m screenModel) list(width int) string {
	label := m.renderer.NewStyle().Foreground(lipgloss.Color(colorDim)).Render("~ " + sectionNames[m.section] + " ~")
	var b strings.Builder
	b.WriteString(label)
	b.WriteString("\n\n")
	for i, item := range m.items() {
		if i == m.cursor {
			b.WriteString(m.renderer.NewStyle().
				Foreground(lipgloss.Color(colorOnPurple)).
				Background(lipgloss.Color(colorPurple)).
				Width(width).
				Render(item))
		} else {
			b.WriteString(item)
		}
		b.WriteString("\n")
	}
	return b.String()
}

func (m screenModel) detail(width int) string {
	body := m.detailText()
	return m.renderer.NewStyle().Width(width).Foreground(lipgloss.Color(colorText)).Render(body)
}

func (m screenModel) detailText() string {
	if m.overlay != "" {
		return strings.TrimRight(m.overlay, "\n")
	}
	if m.step > 0 {
		prompts := []string{"Name", "Email or phone", "Message"}
		return prompts[m.step-1] + "\n\n" + m.entry + "█"
	}
	switch m.section {
	case sectionHome:
		return m.homeText()
	case sectionExperience:
		return m.roleText()
	case sectionPortfolio:
		return m.projectText()
	case sectionPhoto:
		return m.photoText()
	case sectionContact:
		return m.contactText()
	case sectionFit:
		return m.fitTextView()
	default:
		return ""
	}
}

func (m screenModel) homeText() string {
	name, headline, sub := m.identity()
	text := name + "\n\n" + headline + "\n\n" + sub
	if m.shell.Problem != "" && !m.loaded() {
		text += "\n\n" + m.shell.Problem
	}
	return text
}

func (m screenModel) identity() (string, string, string) {
	name := m.shell.Snap.Identity.Name
	if name == "" {
		name = "Erik V Gratz"
	}
	headline := m.shell.Snap.Identity.Headline
	if headline == "" {
		headline = "I build small, useful tools for the web."
	}
	sub := m.shell.Snap.Identity.Sub
	if sub == "" {
		sub = "I have a passion for computers. I create solutions to modern web problems."
	}
	return name, headline, sub
}

func (m screenModel) loaded() bool {
	snap := m.shell.Snap
	return len(snap.Experience) > 0 || len(snap.Portfolio) > 0 || snap.Contact.Email != ""
}

func (m screenModel) roleText() string {
	if !m.loaded() {
		return m.blocked()
	}
	roles := m.shell.Snap.Experience
	if len(roles) == 0 {
		return "No roles in the work history."
	}
	role := roles[clamp(m.cursor, len(roles))]
	var b strings.Builder
	fmt.Fprintf(&b, "%s\n%s", role.Title, role.Company)
	if role.Location != "" {
		fmt.Fprintf(&b, "  ·  %s", role.Location)
	}
	if role.Timeframe != "" {
		fmt.Fprintf(&b, "\n%s", role.Timeframe)
	}
	if len(role.Bullets) > 0 {
		b.WriteString("\n")
	}
	for _, bullet := range role.Bullets {
		fmt.Fprintf(&b, "\n• %s", bullet)
	}
	if len(role.Technologies) > 0 {
		fmt.Fprintf(&b, "\n\n%s", strings.Join(role.Technologies, "   "))
	}
	return b.String()
}

func (m screenModel) projectText() string {
	if !m.loaded() {
		return m.blocked()
	}
	projects := m.shell.Snap.Portfolio
	if len(projects) == 0 {
		return "No portfolio entries."
	}
	project := projects[clamp(m.cursor, len(projects))]
	text := project.Title + "\n\n" + project.Text
	if project.Link != "" {
		text += "\n\n" + project.Link
	}
	return text
}

func (m screenModel) photoText() string {
	if !m.loaded() {
		return m.blocked()
	}
	photos := m.shell.Snap.Photos
	if len(photos) == 0 {
		return "No photos published yet. The frames stay on the website."
	}
	photo := photos[clamp(m.cursor, len(photos))]
	var b strings.Builder
	b.WriteString(photo.Title)
	if photo.Description != "" {
		fmt.Fprintf(&b, "\n\n%s", photo.Description)
	}
	if len(photo.Tags) > 0 {
		fmt.Fprintf(&b, "\n\n%s", strings.Join(photo.Tags, "   "))
	}
	if photo.URL != "" {
		fmt.Fprintf(&b, "\n\n%s", photo.URL)
	}
	return b.String()
}

func (m screenModel) contactText() string {
	if !m.loaded() {
		return m.blocked()
	}
	contact := m.shell.Snap.Contact
	switch m.cursor {
	case 1:
		return "Phone\n\n" + contact.Phone
	case 2:
		return "Resume\n\n" + contact.ResumeURL
	case 3:
		return "Leave a note. It goes to the same inbox as the website."
	default:
		return "Email\n\n" + contact.Email
	}
}

func (m screenModel) fitTextView() string {
	text := "Same assistant as the website.\n\n" + m.fitText + "█"
	if m.busy {
		text += "\n\nasking…"
	}
	if m.fitReply != "" {
		text += "\n\n" + m.fitReply
	}
	if !m.loaded() && m.shell.Problem != "" {
		text += "\n\n" + m.shell.Problem
	}
	return text
}

func (m screenModel) blocked() string {
	if m.shell.Problem != "" {
		return m.shell.Problem
	}
	return "The site snapshot has nothing in this section."
}

func (m screenModel) items() []string {
	if m.shell.Problem != "" && !m.loaded() {
		if m.section == sectionHome {
			name, _, _ := m.identity()
			return []string{name}
		}
		return []string{"unavailable"}
	}
	switch m.section {
	case sectionHome:
		name, _, _ := m.identity()
		return []string{name}
	case sectionExperience:
		items := make([]string, 0, len(m.shell.Snap.Experience))
		for _, role := range m.shell.Snap.Experience {
			items = append(items, role.Company)
		}
		return orNone(items)
	case sectionPortfolio:
		items := make([]string, 0, len(m.shell.Snap.Portfolio))
		for _, project := range m.shell.Snap.Portfolio {
			items = append(items, project.Title)
		}
		return orNone(items)
	case sectionPhoto:
		items := make([]string, 0, len(m.shell.Snap.Photos))
		for _, photo := range m.shell.Snap.Photos {
			items = append(items, photo.Title)
		}
		return orNone(items)
	case sectionContact:
		return []string{"Email", "Phone", "Resume", "Write a note"}
	case sectionFit:
		return []string{"Ask"}
	default:
		return nil
	}
}

func orNone(items []string) []string {
	if len(items) == 0 {
		return []string{"(none)"}
	}
	return items
}

func clamp(index, length int) int {
	if index < 0 || length == 0 {
		return 0
	}
	if index >= length {
		return length - 1
	}
	return index
}

func dropLast(value string) string {
	runes := []rune(value)
	if len(runes) == 0 {
		return ""
	}
	return string(runes[:len(runes)-1])
}
