package tui

import (
	"encoding/base64"
	"errors"
	"fmt"
	"io"
	"strings"
	"time"

	"erikgratz.com/terminal/internal/site"
	"github.com/charmbracelet/bubbles/viewport"
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
	linkMode    bool
	linkCursor  int
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
	fitFollowup string
	fitPending  string
	fitReply    string
	fitErr      string
	fitViewport viewport.Model
	busy        bool
}

func newScreen(shell *Shell, out io.Writer) screenModel {
	renderer := lipgloss.NewRenderer(out)
	renderer.SetColorProfile(termenv.ANSI256)
	fitViewport := viewport.New(45, 16)
	fitViewport.MouseWheelEnabled = false
	model := screenModel{
		shell:       shell,
		renderer:    renderer,
		width:       80,
		height:      24,
		fitViewport: fitViewport,
	}
	return model.resize(80, 24)
}

func (m screenModel) resize(width, height int) screenModel {
	m.width = width
	m.height = height
	m.fitViewport.Width, m.fitViewport.Height = m.fitViewportDimensions()
	m.syncFitViewport()
	return m
}

func (m screenModel) fitViewportDimensions() (int, int) {
	fit := m
	fit.section = sectionFit
	fit.cursor = fit.fitField
	layout := fit.panelLayout()
	return layout.rightContentWidth, layout.contentHeight
}

func (m screenModel) Init() tea.Cmd { return nil }

func (m screenModel) Update(msg tea.Msg) (tea.Model, tea.Cmd) {
	switch msg := msg.(type) {
	case tea.WindowSizeMsg:
		m = m.resize(msg.Width, msg.Height)
	case fitMsg:
		m.busy = false
		if msg.err != nil {
			m.fitErr = msg.err.Error()
			m.fitPending = ""
			m.syncFitViewport()
			m.fitViewport.GotoBottom()
			return m, nil
		}
		if m.fitPending != "" {
			m.fitText = m.fitPending
		}
		m.fitPending = ""
		m.fitFollowup = ""
		m.fitReply = msg.answer
		m.fitErr = ""
		m.fitField = 2
		m.cursor = 2
		m.syncFitViewport()
		m.showFitResponse()
	case bingoFinishedMsg:
		if msg.err != nil {
			return m.showNotice("Bingo stopped: " + msg.err.Error())
		}
		return m.showNotice("Bingo finished. Welcome back.")
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

type bingoFinishedMsg struct{ err error }

func (m screenModel) key(msg tea.KeyMsg) (tea.Model, tea.Cmd) {
	if msg.Type == tea.KeyCtrlC || msg.Type == tea.KeyCtrlD {
		return m, tea.Quit
	}
	if m.linkMode {
		return m.linkKey(msg)
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

func (m screenModel) linkKey(msg tea.KeyMsg) (tea.Model, tea.Cmd) {
	links := m.linkTargets()
	switch msg.Type {
	case tea.KeyEsc, tea.KeyLeft:
		m.linkMode = false
		m.linkCursor = 0
	case tea.KeyUp:
		m.linkCursor = clamp(m.linkCursor-1, len(links))
	case tea.KeyDown:
		m.linkCursor = clamp(m.linkCursor+1, len(links))
	case tea.KeyEnter:
		return m.requestLink(links, true)
	case tea.KeyRunes:
		switch string(msg.Runes) {
		case "c":
			return m.requestLink(links, false)
		case "o":
			return m.requestLink(links, true)
		case "q":
			m.linkMode = false
			m.linkCursor = 0
		}
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
	if m.section == sectionFit {
		return m.fitKey(msg)
	}
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
		m.noteName, m.noteContact, m.noteMessage, m.noteStatus = "", "", "", ""
		m.noteField = 0
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

func (m screenModel) fitKey(msg tea.KeyMsg) (tea.Model, tea.Cmd) {
	switch msg.Type {
	case tea.KeyLeft:
		return m.bump(-1), nil
	case tea.KeyRight:
		return m.bump(1), nil
	case tea.KeyTab:
		m = m.moveField(1)
	case tea.KeyShiftTab:
		m = m.moveField(-1)
	case tea.KeyUp, tea.KeyDown, tea.KeyPgUp, tea.KeyPgDown:
		var cmd tea.Cmd
		m.fitViewport, cmd = m.fitViewport.Update(msg)
		return m, cmd
	case tea.KeyEsc:
		m.fitJob, m.fitText, m.fitFollowup = "", "", ""
		m.fitPending, m.fitReply, m.fitErr = "", "", ""
		m.fitField = 0
		m.cursor = 0
		m.busy = false
		m.syncFitViewport()
		m.fitViewport.GotoTop()
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
		command, err := m.shell.bingoCommand()
		if err != nil {
			return m.showNotice(err.Error())
		}
		m.notice = "Launching the original Auto-Bingo CLI…"
		return m, tea.Exec(&pausedCommand{command: command, pause: 3 * time.Second}, func(err error) tea.Msg {
			return bingoFinishedMsg{err: err}
		})
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
	m.linkMode = false
	m.linkCursor = 0
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
	case m.section == sectionFit && m.fitField == 1:
		m.fitText += value
	case m.section == sectionFit:
		m.fitFollowup += value
	case m.noteField == 0:
		m.noteName += value
	case m.noteField == 1:
		m.noteContact += value
	default:
		m.noteMessage += value
	}
	if m.section == sectionFit {
		m.syncFitViewport()
		m.showFitField()
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
	case m.section == sectionFit && m.fitField == 1:
		m.fitText = dropLast(m.fitText)
	case m.section == sectionFit:
		m.fitFollowup = dropLast(m.fitFollowup)
	case m.noteField == 0:
		m.noteName = dropLast(m.noteName)
	case m.noteField == 1:
		m.noteContact = dropLast(m.noteContact)
	default:
		m.noteMessage = dropLast(m.noteMessage)
	}
	if m.section == sectionFit {
		m.syncFitViewport()
		m.showFitField()
	}
	return m
}

func (m screenModel) moveField(delta int) screenModel {
	if m.section == sectionFit {
		m.fitField += delta
		if m.fitField < 0 {
			m.fitField = 0
		}
		maxField := 1
		if m.fitReply != "" {
			maxField = 2
		}
		if m.fitField > maxField {
			m.fitField = maxField
		}
		m.cursor = m.fitField
		m.syncFitViewport()
		m.showFitField()
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
	links := m.linkTargets()
	if len(links) == 0 {
		return m.showNotice("No link is published for this item.")
	}
	m.linkMode = true
	m.linkCursor = 0
	m.notice = ""
	return m, nil
}

func (m screenModel) requestLink(links []linkRef, requestOpen bool) (tea.Model, tea.Cmd) {
	if len(links) == 0 {
		return m.showNotice("No link is published for this item.")
	}
	link := links[clamp(m.linkCursor, len(links))]
	href := strings.TrimSpace(link.Href)
	if href == "" || href == "mailto:" {
		return m.showNotice("No link is published for this item.")
	}
	m.noticeID++
	id := m.noticeID
	sequence := copySequence(href)
	if requestOpen {
		sequence += openSequence(href)
		m.notice = "Copied. iTerm also received an open request."
	} else {
		m.notice = "Copied to your clipboard."
	}
	return m, tea.Exec(&ttyWrite{seq: sequence}, func(error) tea.Msg {
		return startNoticeTimerMsg{id: id}
	})
}

func (m screenModel) showNotice(notice string) (tea.Model, tea.Cmd) {
	m.noticeID++
	id := m.noticeID
	m.notice = notice
	return m, tea.Tick(2*time.Second, func(time.Time) tea.Msg {
		return clearNoticeMsg{id: id}
	})
}

func (m screenModel) submit() (tea.Model, tea.Cmd) {
	if m.section == sectionFit {
		if m.busy {
			return m, nil
		}
		if m.fitField == 0 {
			m = m.moveField(1)
			return m, nil
		}
		question := strings.TrimSpace(m.fitText)
		if m.fitField == 2 {
			question = strings.TrimSpace(m.fitFollowup)
		}
		if question == "" {
			m.fitErr = "A question is required."
			m.syncFitViewport()
			m.fitViewport.GotoBottom()
			return m, nil
		}
		m.busy = true
		m.fitPending = question
		m.fitErr = ""
		m.syncFitViewport()
		if m.fitField == 2 {
			m.fitViewport.GotoBottom()
		} else {
			m.showFitField()
		}
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

type panelLayout struct {
	leftBoxWidth      int
	leftContentWidth  int
	rightBoxWidth     int
	rightContentWidth int
	boxHeight         int
	contentHeight     int
}

func (m screenModel) panelLayout() panelLayout {
	bodyHeight := m.height - lipgloss.Height(m.header()) - lipgloss.Height(m.footer())
	if actions := m.actionBar(); actions != "" {
		bodyHeight -= lipgloss.Height(actions)
	}
	if bodyHeight < 6 {
		bodyHeight = 6
	}

	box := m.box()
	borderWidth := box.GetHorizontalBorderSize()
	paddingWidth := box.GetHorizontalPadding()
	borderHeight := box.GetVerticalBorderSize()
	paddingHeight := box.GetVerticalPadding()

	leftOuterWidth := 30
	if leftOuterWidth > m.width/2 {
		leftOuterWidth = m.width / 3
	}
	leftBoxWidth := max(leftOuterWidth-borderWidth, paddingWidth+16)
	leftContentWidth := leftBoxWidth - paddingWidth
	actualLeftOuterWidth := leftBoxWidth + borderWidth

	rightOuterWidth := m.width - actualLeftOuterWidth - 1
	rightBoxWidth := max(rightOuterWidth-borderWidth, paddingWidth+20)
	rightContentWidth := rightBoxWidth - paddingWidth
	boxHeight := max(1, bodyHeight-borderHeight)
	contentHeight := max(1, boxHeight-paddingHeight)

	return panelLayout{
		leftBoxWidth:      leftBoxWidth,
		leftContentWidth:  leftContentWidth,
		rightBoxWidth:     rightBoxWidth,
		rightContentWidth: rightContentWidth,
		boxHeight:         boxHeight,
		contentHeight:     contentHeight,
	}
}

func (m screenModel) View() string {
	if m.width < 48 || m.height < 12 {
		return "This profile needs a terminal at least 48 columns wide.\n"
	}
	header := m.header()
	actions := m.actionBar()
	layout := m.panelLayout()
	box := m.box()
	left := box.
		Width(layout.leftBoxWidth).
		Height(layout.boxHeight).
		Render(m.list(layout.leftContentWidth))
	right := box.
		Width(layout.rightBoxWidth).
		Height(layout.boxHeight).
		Render(m.detail(layout.rightContentWidth, layout.contentHeight))
	parts := []string{
		header,
		lipgloss.JoinHorizontal(lipgloss.Top, left, " ", right),
	}
	if actions != "" {
		parts = append(parts, actions)
	}
	parts = append(parts, m.footer())
	return lipgloss.JoinVertical(lipgloss.Left, parts...)
}

func (m screenModel) box() lipgloss.Style {
	return m.renderer.NewStyle().
		Border(lipgloss.RoundedBorder()).
		BorderForeground(lipgloss.Color(colorLine)).
		Padding(0, 1)
}

func (m screenModel) header() string {
	brand := m.renderer.NewStyle().
		Foreground(lipgloss.Color(colorPurpleText)).
		Bold(true).
		Render("ERIKGRATZ.COM")
	mode := m.renderer.NewStyle().
		Foreground(lipgloss.Color(colorDim)).
		Render("  /  SSH PROFILE")
	title := brand + mode
	labels := sectionNames
	if m.width < 74 {
		labels = []string{"home", "work", "code", "photo", "mail", "fit"}
	}
	nav := make([]string, 0, len(sectionNames))
	for i, name := range labels {
		style := m.renderer.NewStyle().
			Foreground(lipgloss.Color(colorDim))
		if section(i) == m.section {
			style = style.
				Foreground(lipgloss.Color(colorOnPurple)).
				Background(lipgloss.Color(colorPurple)).
				Bold(true)
		}
		nav = append(nav, style.Render(fmt.Sprintf(" %d %s ", i+1, strings.ToUpper(name))))
	}
	navigation := strings.Join(nav, " ")
	if lipgloss.Width(navigation) > m.width {
		nav = nav[:0]
		for i := range sectionNames {
			label := fmt.Sprintf(" %d ", i+1)
			style := m.renderer.NewStyle().Foreground(lipgloss.Color(colorDim))
			if section(i) == m.section {
				style = style.
					Foreground(lipgloss.Color(colorOnPurple)).
					Background(lipgloss.Color(colorPurple)).
					Bold(true)
			}
			nav = append(nav, style.Render(label))
		}
		navigation = strings.Join(nav, " ")
	}
	rule := m.renderer.NewStyle().
		Foreground(lipgloss.Color(colorPurple)).
		Render(strings.Repeat("━", m.width))
	return lipgloss.JoinVertical(lipgloss.Left, title, navigation, rule)
}

func (m screenModel) footer() string {
	hint := "←/→ sections  ·  ↑/↓ items  ·  1–6 jump  ·  q quit  ·  b bingo"
	switch {
	case m.notice != "":
		hint = m.notice
	case m.linkMode:
		hint = "↑/↓ choose  ·  enter copy + open in iTerm  ·  c copy  ·  ⌘-click link  ·  esc back"
		if m.width < 96 {
			hint = "↑/↓ choose  ·  enter open/copy  ·  c copy  ·  ⌘-click  ·  esc back"
		}
	case m.section == sectionFit:
		hint = fmt.Sprintf("tab fields  ·  ↑/↓ or pgup/pgdn scroll  ·  enter ask  ·  esc clear  ·  %3.0f%%", m.fitViewport.ScrollPercent()*100)
	case m.section == sectionContact && m.cursor == 3:
		hint = "↑/↓ fields  ·  type  ·  enter next/send  ·  esc clear"
	case len(m.linkTargets()) > 0:
		hint = "enter link launcher  ·  ⌘-click a link above  ·  ←/→ sections  ·  ↑/↓ items"
		if m.width < 96 {
			hint = "enter links  ·  ⌘-click above  ·  ←/→ sections  ·  ↑/↓ items"
		}
	}
	return m.renderer.NewStyle().
		Foreground(lipgloss.Color(colorOnPurple)).
		Background(lipgloss.Color(colorPurple)).
		Width(m.width).
		Render(" " + hint)
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
	for _, link := range project.Links {
		add(link.Text, link.Href)
	}
	add("Open", project.Link)
	return refs
}

func (m screenModel) actionBar() string {
	refs := m.linkTargets()
	if len(refs) == 0 {
		return ""
	}
	lines := make([]string, 0, len(refs))
	for i, ref := range refs {
		label := "↗ " + ref.Text
		if m.linkMode && i == m.linkCursor {
			label = "› " + label
		}
		lines = append(lines, hyperlink(label, ref.Href))
	}
	prefix := m.renderer.NewStyle().
		Foreground(lipgloss.Color(colorDim)).
		Bold(true).
		Render(" LINKS  ")
	line := prefix + strings.Join(lines, "   ")
	if ansi.StringWidth(line) <= m.width {
		return line
	}
	return lipgloss.JoinVertical(lipgloss.Left, append([]string{prefix}, lines...)...)
}

func (m screenModel) list(width int) string {
	label := m.renderer.NewStyle().
		Foreground(lipgloss.Color(colorPurpleText)).
		Bold(true).
		Render(strings.ToUpper(sectionNames[m.section]))
	var b strings.Builder
	b.WriteString(label)
	b.WriteString("\n\n")
	for i, item := range m.items() {
		if i == m.cursor {
			b.WriteString(m.renderer.NewStyle().
				Foreground(lipgloss.Color(colorOnPurple)).
				Background(lipgloss.Color(colorPurple)).
				Width(width).
				Render("› " + item))
		} else {
			b.WriteString("  " + item)
		}
		b.WriteString("\n")
	}
	return b.String()
}

func (m screenModel) detail(width, height int) string {
	if m.section == sectionFit && !m.linkMode && m.overlay == "" {
		fitViewport := m.fitViewport
		fitViewport.Width = width
		fitViewport.Height = height
		rendered := m.fitRender(width)
		fitViewport.SetContent(rendered.content)
		return fitViewport.View()
	}
	body := m.detailText(width)
	return m.renderer.NewStyle().Width(width).Foreground(lipgloss.Color(colorText)).Render(body)
}

func (m screenModel) detailText(width int) string {
	if m.overlay != "" {
		return strings.TrimRight(m.overlay, "\n")
	}
	if m.linkMode {
		return m.linkLauncher(width)
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
		return ""
	default:
		return ""
	}
}

func (m screenModel) linkLauncher(width int) string {
	links := m.linkTargets()
	if len(links) == 0 {
		return m.muted("No link is published for this item.")
	}
	link := links[clamp(m.linkCursor, len(links))]
	title := m.heading("OPEN LINK")
	name := m.renderer.NewStyle().
		Foreground(lipgloss.Color(colorText)).
		Bold(true).
		Render("↗ " + link.Text)
	target := wrappedHyperlink(link.Href, link.Href, width)
	instructions := m.muted("⌘-click the URL to open it in this terminal.\nEnter copies it and asks iTerm to open it.\nPress c to copy, or esc to return.")
	return title + "\n\n" + name + "\n\n" + target + "\n\n" + instructions
}

func (m screenModel) homeText() string {
	name, headline, sub := m.identity()
	text := m.heading(name)
	if headline != "" {
		text += "\n\n" + m.renderer.NewStyle().
			Foreground(lipgloss.Color(colorText)).
			Bold(true).
			Render(headline)
	}
	if sub != "" {
		text += "\n\n" + sub
	}
	if m.shell.Problem != "" && !m.loaded() {
		text += "\n\n" + m.renderer.NewStyle().
			Foreground(lipgloss.Color("203")).
			Render(m.shell.Problem)
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
	b.WriteString(m.heading(role.Title))
	b.WriteString("\n")
	b.WriteString(m.renderer.NewStyle().Bold(true).Render(role.Company))
	meta := strings.Join(nonEmpty(role.Location, role.Timeframe), "  ·  ")
	if meta != "" {
		b.WriteString("\n")
		b.WriteString(m.muted(meta))
	}
	if len(role.Bullets) > 0 {
		b.WriteString("\n")
	}
	for _, bullet := range role.Bullets {
		fmt.Fprintf(&b, "\n%s %s", m.accent("◆"), bullet)
	}
	if len(role.Technologies) > 0 {
		fmt.Fprintf(&b, "\n\n%s\n%s", m.muted("TOOLS"), m.accent(strings.Join(role.Technologies, "  ·  ")))
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
	return m.heading(project.Title) + "\n\n" + project.Text
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
	b.WriteString(m.heading(photo.Title))
	if photo.Description != "" {
		fmt.Fprintf(&b, "\n\n%s", photo.Description)
	}
	if len(photo.Tags) > 0 {
		fmt.Fprintf(&b, "\n\n%s", m.muted("#"+strings.Join(photo.Tags, "  #")))
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
		return m.heading("Phone") + "\n\n" + contact.Phone
	case 2:
		return m.heading("Resume") + "\n\nOpen the current résumé in Google Docs."
	case 3:
		return m.noteForm()
	default:
		return m.heading("Email") + "\n\n" + contact.Email
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
	b.WriteString(m.heading("WRITE A NOTE"))
	b.WriteString("\n")
	b.WriteString(m.muted("Same inbox as the website."))
	for i, field := range fields {
		fmt.Fprintf(&b, "\n\n%s\n%s", m.muted(strings.ToUpper(field.label)), m.fieldLine(field.value, i == m.noteField))
	}
	if m.noteStatus != "" {
		fmt.Fprintf(&b, "\n\n%s", m.accent(m.noteStatus))
	}
	return b.String()
}

type fitRendered struct {
	content       string
	fieldStarts   [3]int
	fieldEnds     [3]int
	responseStart int
}

func (m screenModel) fitRender(width int) fitRendered {
	if width < 1 {
		width = 1
	}
	rendered := fitRendered{responseStart: -1}
	lines := make([]string, 0, 24)
	appendBlock := func(value string) (int, int) {
		start := len(lines)
		block := m.renderer.NewStyle().Width(width).Render(value)
		lines = append(lines, strings.Split(block, "\n")...)
		return start, len(lines) - 1
	}
	blank := func() {
		lines = append(lines, "")
	}

	appendBlock(m.heading("ASK ABOUT FIT"))
	blank()
	rendered.fieldStarts[0], rendered.fieldEnds[0] = appendBlock(
		m.muted("JOB DESCRIPTION · OPTIONAL") + "\n" + m.fieldLine(m.fitJob, m.fitField == 0),
	)
	blank()
	rendered.fieldStarts[1], rendered.fieldEnds[1] = appendBlock(
		m.muted("YOUR QUESTION") + "\n" + m.fieldLine(m.fitText, m.fitField == 1),
	)

	if m.fitReply != "" {
		blank()
		rendered.responseStart, _ = appendBlock(m.heading("FIT RESPONSE") + "\n\n" + m.fitReply)
		blank()
		rendered.fieldStarts[2], rendered.fieldEnds[2] = appendBlock(
			m.muted("ASK FURTHER QUESTION") + "\n" + m.fieldLine(m.fitFollowup, m.fitField == 2),
		)
	} else {
		rendered.fieldStarts[2] = len(lines)
		rendered.fieldEnds[2] = len(lines)
	}

	switch {
	case m.busy:
		blank()
		appendBlock(m.accent("ASKING…"))
	case m.fitErr != "":
		blank()
		appendBlock(m.renderer.NewStyle().Foreground(lipgloss.Color("203")).Render(m.fitErr))
	}
	if !m.loaded() && m.shell.Problem != "" {
		blank()
		appendBlock(m.shell.Problem)
	}
	rendered.content = strings.Join(lines, "\n")
	return rendered
}

func (m *screenModel) syncFitViewport() {
	rendered := m.fitRender(m.fitViewport.Width)
	m.fitViewport.SetContent(rendered.content)
}

func (m *screenModel) showFitField() {
	rendered := m.fitRender(m.fitViewport.Width)
	m.fitViewport.SetContent(rendered.content)
	field := clamp(m.fitField, len(rendered.fieldEnds))
	m.fitViewport.SetYOffset(visibleOffset(
		m.fitViewport.YOffset,
		m.fitViewport.Height,
		rendered.fieldStarts[field],
		rendered.fieldEnds[field],
	))
}

func (m *screenModel) showFitResponse() {
	rendered := m.fitRender(m.fitViewport.Width)
	m.fitViewport.SetContent(rendered.content)
	if rendered.responseStart >= 0 {
		m.fitViewport.SetYOffset(rendered.responseStart)
	}
}

func visibleOffset(current, height, start, end int) int {
	if height < 1 || start < current {
		return start
	}
	if end >= current+height {
		return end - height + 1
	}
	return current
}

func (m screenModel) fieldLine(value string, active bool) string {
	if active {
		return m.accent("▏") + value + m.accent("█")
	}
	if value == "" {
		return m.muted("▏")
	}
	return m.muted("▏") + value
}

func (m screenModel) heading(value string) string {
	return m.renderer.NewStyle().
		Foreground(lipgloss.Color(colorPurpleText)).
		Bold(true).
		Render(value)
}

func (m screenModel) accent(value string) string {
	return m.renderer.NewStyle().
		Foreground(lipgloss.Color(colorPurpleText)).
		Render(value)
}

func (m screenModel) muted(value string) string {
	return m.renderer.NewStyle().
		Foreground(lipgloss.Color(colorDim)).
		Render(value)
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
		return []string{"Overview"}
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
		items := []string{"Job description", "Question"}
		if m.fitReply != "" {
			items = append(items, "Ask further question")
		}
		return items
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

func nonEmpty(values ...string) []string {
	filtered := make([]string, 0, len(values))
	for _, value := range values {
		if value = strings.TrimSpace(value); value != "" {
			filtered = append(filtered, value)
		}
	}
	return filtered
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

func wrappedHyperlink(label, href string, width int) string {
	if width < 1 {
		return hyperlink(label, href)
	}
	lines := strings.Split(ansi.Hardwrap(label, width, false), "\n")
	for i, line := range lines {
		lines[i] = hyperlink(line, href)
	}
	return strings.Join(lines, "\n")
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

func copySequence(target string) string {
	encoded := base64.StdEncoding.EncodeToString([]byte(target))
	return "\x1b]52;c;" + encoded + "\x07"
}

type ttyWrite struct {
	seq string
	out io.Writer
}

func (t *ttyWrite) SetStdin(io.Reader) {}

func (t *ttyWrite) SetStdout(w io.Writer) { t.out = w }

func (t *ttyWrite) SetStderr(io.Writer) {}

func (t *ttyWrite) Run() error {
	if t.out == nil {
		return errors.New("no terminal")
	}
	_, err := io.WriteString(t.out, t.seq)
	return err
}

func dropLast(value string) string {
	runes := []rune(value)
	if len(runes) == 0 {
		return ""
	}
	return string(runes[:len(runes)-1])
}
