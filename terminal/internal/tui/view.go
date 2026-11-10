package tui

import (
	"encoding/base64"
	"errors"
	"fmt"
	"io"
	"os"
	"strings"
	"time"

	"erikgratz.com/terminal/internal/site"
	tea "github.com/charmbracelet/bubbletea"
	"github.com/charmbracelet/lipgloss"
	"github.com/charmbracelet/x/ansi"
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
	shell       *Shell
	renderer    *lipgloss.Renderer
	width       int
	height      int
	section     section
	cursor      int
	overlay     string
	notice      string
	noticeID    int
	noteField   int
	noteName    string
	noteContact string
	noteMessage string
	noteStatus  string
	fitField    int
	fitJob      string
	fitText     string
	fitReply    string
	fitErr      string
	busy        bool
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
			m.fitErr = msg.err.Error()
			return m, nil
		}
		m.fitReply = msg.answer
		m.fitErr = ""
	case noteMsg:
		m.noteStatus = string(msg)
		if strings.Contains(m.noteStatus, "sent") {
			m.noteName, m.noteContact, m.noteMessage = "", "", ""
			m.noteField = 0
		}
	case startNoticeTimerMsg:
		if msg.id != m.noticeID {
			return m, nil
		}
		id := msg.id
		return m, tea.Tick(2*time.Second, func(time.Time) tea.Msg {
			return clearNoticeMsg{id: id}
		})
	case clearNoticeMsg:
		if msg.id == m.noticeID {
			m.notice = ""
		}
	case tea.KeyMsg:
		return m.key(msg)
	}
	return m, nil
}

type startNoticeTimerMsg struct{ id int }

type clearNoticeMsg struct{ id int }

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
	if m.section == sectionFit {
		return true
	}
	return m.section == sectionContact && m.cursor == 3
}

func (m screenModel) typeKey(msg tea.KeyMsg) (tea.Model, tea.Cmd) {
	switch msg.Type {
	case tea.KeyLeft:
		m = m.bump(-1)
	case tea.KeyRight:
		m = m.bump(1)
	case tea.KeyTab:
		m = m.moveField(1)
	case tea.KeyUp:
		m = m.moveField(-1)
	case tea.KeyDown:
		m = m.moveField(1)
	case tea.KeyEsc:
		m.fitJob, m.fitText, m.fitReply, m.fitErr = "", "", "", ""
		m.noteName, m.noteContact, m.noteMessage, m.noteStatus = "", "", "", ""
		m.noteField = 0
		m.fitField = 0
	case tea.KeyEnter:
		return m.submit()
	case tea.KeyBackspace:
		m = m.backspace()
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
	}
	return m, nil
}

func (m screenModel) bump(delta int) screenModel {
	count := len(sectionNames)
	m.section = section((int(m.section) + delta + count) % count)
	m.cursor = 0
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
	switch {
	case m.section == sectionFit && m.fitField == 0:
		m.fitJob += value
	case m.section == sectionFit:
		m.fitText += value
	case m.noteField == 0:
		m.noteName += value
	case m.noteField == 1:
		m.noteContact += value
	default:
		m.noteMessage += value
	}
	return m
}

func (m screenModel) noteValue() string {
	switch m.noteField {
	case 0:
		return m.noteName
	case 1:
		return m.noteContact
	default:
		return m.noteMessage
	}
}

func (m screenModel) backspace() screenModel {
	switch {
	case m.section == sectionFit && m.fitField == 0:
		m.fitJob = dropLast(m.fitJob)
	case m.section == sectionFit:
		m.fitText = dropLast(m.fitText)
	case m.noteField == 0:
		m.noteName = dropLast(m.noteName)
	case m.noteField == 1:
		m.noteContact = dropLast(m.noteContact)
	default:
		m.noteMessage = dropLast(m.noteMessage)
	}
	return m
}

func (m screenModel) moveField(delta int) screenModel {
	if m.section == sectionFit {
		m.fitField += delta
		if m.fitField < 0 {
			m.fitField = 0
		}
		if m.fitField > 1 {
			m.fitField = 1
		}
		m.cursor = m.fitField
		return m
	}
	next := m.noteField + delta
	if next < 0 {
		m.cursor = 2
		return m
	}
	if next > 2 {
		m.noteField = 2
		return m
	}
	m.noteField = next
	return m
}

func (m screenModel) activate() (tea.Model, tea.Cmd) {
	switch m.section {
	case sectionContact:
		if m.cursor == 3 {
			m.noteField = 0
			return m, nil
		}
		if m.cursor != 0 && m.cursor != 2 {
			return m, nil
		}
	case sectionHome, sectionExperience, sectionFit:
		return m, nil
	}
	return m.open(m.linkTargets())
}

func (m screenModel) open(links []linkRef) (tea.Model, tea.Cmd) {
	hrefs := make([]string, 0, len(links))
	for _, link := range links {
		href := strings.TrimSpace(link.Href)
		if href == "" || href == "mailto:" {
			continue
		}
		hrefs = append(hrefs, href)
	}
	m.noticeID++
	id := m.noticeID
	if len(hrefs) == 0 {
		m.notice = "Nothing to open."
		return m, tea.Tick(2*time.Second, func(time.Time) tea.Msg {
			return clearNoticeMsg{id: id}
		})
	}
	m.notice = "Opening…"
	return m, tea.Exec(&ttyWrite{seq: openSequence(hrefs...), live: true, pause: 200 * time.Millisecond}, func(error) tea.Msg {
		return startNoticeTimerMsg{id: id}
	})
}

func (m screenModel) submit() (tea.Model, tea.Cmd) {
	if m.section == sectionFit {
		question := strings.TrimSpace(m.fitText)
		if m.busy {
			return m, nil
		}
		if question == "" {
			m.fitErr = "A question is required."
			m.fitField = 1
			m.cursor = 1
			return m, nil
		}
		m.busy = true
		m.fitErr = ""
		m.fitReply = ""
		return m, askFit(m.shell.API, question, strings.TrimSpace(m.fitJob))
	}
	if m.noteField < 2 {
		if strings.TrimSpace(m.noteValue()) == "" {
			return m, nil
		}
		m.noteField++
		return m, nil
	}
	name := strings.TrimSpace(m.noteName)
	contact := strings.TrimSpace(m.noteContact)
	message := strings.TrimSpace(m.noteMessage)
	if name == "" || contact == "" || message == "" {
		m.noteStatus = "Name, email or phone, and a message are required."
		return m, nil
	}
	m.noteStatus = "sending…"
	return m, sendNote(m.shell.API, name, contact, message)
}

func askFit(api Backend, question, job string) tea.Cmd {
	return func() tea.Msg {
		if api == nil {
			return fitMsg{err: errors.New("fit is offline")}
		}
		answer, err := api.Fit(question, job)
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
	links := m.linkBlock()
	linkLines := 0
	if links != "" {
		linkLines = strings.Count(links, "\n") + 1
	}
	bodyHeight := m.height - lipgloss.Height(m.tabs()) - 2 - linkLines
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
	parts := []string{
		m.tabs(),
		m.rule(),
		lipgloss.JoinHorizontal(lipgloss.Top, left, right),
		m.footer(),
	}
	if links != "" {
		parts = append(parts, links)
	}
	return lipgloss.JoinVertical(lipgloss.Left, parts...)
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
	hint := "←/→ sections    ↑/↓ items    enter opens    1-6 jump    q quit    b bingo"
	switch {
	case m.section == sectionFit:
		hint = "↑/↓ fields    type    enter ask    esc clear"
	case m.section == sectionContact && m.cursor == 3:
		hint = "↑/↓ fields    type    enter next    enter sends    esc clear"
	case m.notice != "":
		hint = m.notice
	}
	return m.renderer.NewStyle().Foreground(lipgloss.Color(colorDim)).Render(hint)
}

type linkRef struct {
	Text string
	Href string
}

func (m screenModel) linkTargets() []linkRef {
	switch m.section {
	case sectionContact:
		switch m.cursor {
		case 0:
			if email := strings.TrimSpace(m.shell.Snap.Contact.Email); email != "" {
				return []linkRef{{Text: "Email", Href: "mailto:" + email}}
			}
		case 2:
			if href := strings.TrimSpace(m.shell.Snap.Contact.ResumeURL); href != "" {
				return []linkRef{{Text: "Resume", Href: href}}
			}
		}
	case sectionPhoto:
		photos := m.shell.Snap.Photos
		if len(photos) == 0 {
			return nil
		}
		photo := photos[clamp(m.cursor, len(photos))]
		if strings.TrimSpace(photo.URL) == "" {
			return nil
		}
		text := photo.Title
		if text == "" {
			text = "Photo"
		}
		return []linkRef{{Text: text, Href: photo.URL}}
	case sectionPortfolio:
		projects := m.shell.Snap.Portfolio
		if len(projects) == 0 {
			return nil
		}
		return projectLinks(projects[clamp(m.cursor, len(projects))])
	default:
		return nil
	}
	return nil
}

func projectLinks(project site.Project) []linkRef {
	seen := map[string]bool{}
	refs := make([]linkRef, 0, 1+len(project.Links))
	add := func(text, href string) {
		href = strings.TrimSpace(href)
		if href == "" || seen[href] {
			return
		}
		seen[href] = true
		if strings.TrimSpace(text) == "" {
			text = "Open"
		}
		refs = append(refs, linkRef{Text: text, Href: href})
	}
	add("Open", project.Link)
	for _, link := range project.Links {
		add(link.Text, link.Href)
	}
	return refs
}

func (m screenModel) linkBlock() string {
	refs := m.linkTargets()
	if len(refs) == 0 {
		return ""
	}
	lines := make([]string, 0, len(refs))
	for _, ref := range refs {
		lines = append(lines, m.oneLink(ref))
	}
	return strings.Join(lines, "\n")
}

func (m screenModel) oneLink(ref linkRef) string {
	visible := ref.Text
	candidate := ref.Text + "  " + ref.Href
	if ansi.StringWidth(candidate) <= m.width {
		visible = candidate
	} else if ansi.StringWidth(visible) > m.width {
		visible = ansi.Truncate(ref.Text, m.width, "")
	}
	return hyperlink(visible, ref.Href)
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
	text := name
	if headline != "" {
		text += "\n\n" + headline
	}
	if sub != "" {
		text += "\n\n" + sub
	}
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
	headline := strings.TrimSpace(m.shell.Snap.Identity.Headline)
	if retiredHeadline(headline) {
		headline = ""
	}
	sub := m.shell.Snap.Identity.Sub
	if sub == "" {
		sub = "I have a passion for computers. I create solutions to modern web problems."
	}
	return name, headline, sub
}

func retiredHeadline(value string) bool {
	value = strings.TrimRight(strings.TrimSpace(strings.ToLower(value)), ".")
	return value == "i build small, useful tools for the web" || value == "i build small useful tools for the web"
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
	return project.Title + "\n\n" + project.Text
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
		return "Resume\n\nenter opens it"
	case 3:
		return m.noteForm()
	default:
		return "Email\n\n" + contact.Email + "\n\nenter opens it"
	}
}

func (m screenModel) noteForm() string {
	fields := []struct {
		label string
		value string
	}{
		{"Name", m.noteName},
		{"Email or phone", m.noteContact},
		{"Message", m.noteMessage},
	}
	var b strings.Builder
	b.WriteString("Same inbox as the website.")
	for i, field := range fields {
		fmt.Fprintf(&b, "\n\n%s\n%s", field.label, m.fieldLine(field.value, i == m.noteField))
	}
	if m.noteStatus != "" {
		fmt.Fprintf(&b, "\n\n%s", m.noteStatus)
	}
	return b.String()
}

func (m screenModel) fitTextView() string {
	var b strings.Builder
	switch {
	case m.busy:
		b.WriteString("asking…\n\n")
	case m.fitErr != "":
		b.WriteString(m.fitErr + "\n\n")
	case m.fitReply != "":
		b.WriteString(m.fitReply + "\n\n")
	}
	b.WriteString("Job description (optional)\n")
	b.WriteString(m.fieldLine(m.fitJob, m.fitField == 0))
	b.WriteString("\n\nYour question\n")
	b.WriteString(m.fieldLine(m.fitText, m.fitField == 1))
	if !m.loaded() && m.shell.Problem != "" {
		fmt.Fprintf(&b, "\n\n%s", m.shell.Problem)
	}
	return b.String()
}

func (m screenModel) fieldLine(value string, active bool) string {
	if active {
		return value + "█"
	}
	if value == "" {
		return " "
	}
	return value
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
		return []string{"Job description", "Question"}
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

func hyperlink(label, href string) string {
	if label == "" || href == "" {
		return label
	}
	return "\x1b]8;;" + href + "\x1b\\" + label + "\x1b]8;;\x1b\\"
}

func openSequence(targets ...string) string {
	var b strings.Builder
	for _, target := range targets {
		encoded := base64.StdEncoding.EncodeToString([]byte(target))
		b.WriteString("\x1b]1337;OpenURL=:" + encoded + "\x07")
		b.WriteString("\x1b]1337;OpenURL=:" + encoded + "\x1b\\")
	}
	return b.String()
}

type ttyWrite struct {
	seq   string
	out   io.Writer
	live  bool
	pause time.Duration
}

func (t *ttyWrite) SetStdin(io.Reader) {}

func (t *ttyWrite) SetStdout(w io.Writer) { t.out = w }

func (t *ttyWrite) SetStderr(io.Writer) {}

func (t *ttyWrite) Run() error {
	if t.out == nil {
		return errors.New("no terminal")
	}
	_, err := io.WriteString(t.out, t.seq)
	if t.live {
		if tty, openErr := os.OpenFile("/dev/tty", os.O_WRONLY, 0); openErr == nil {
			_, _ = io.WriteString(tty, t.seq)
			_ = tty.Close()
		}
		if t.pause > 0 {
			time.Sleep(t.pause)
		}
	}
	return err
}

func dropLast(value string) string {
	runes := []rune(value)
	if len(runes) == 0 {
		return ""
	}
	return string(runes[:len(runes)-1])
}
