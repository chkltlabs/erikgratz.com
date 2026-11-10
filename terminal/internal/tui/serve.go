package tui

import (
	"bufio"
	"fmt"
	"io"
	"os"
	"strings"

	tea "github.com/charmbracelet/bubbletea"
	"github.com/charmbracelet/ssh"
	"github.com/mattn/go-isatty"
)

func ServeStdio(shell *Shell) {
	ServeIO(os.Stdin, os.Stdout, shell)
}

func ServeIO(in io.Reader, out io.Writer, shell *Shell) {
	if file, ok := in.(*os.File); ok && isatty.IsTerminal(file.Fd()) {
		program := tea.NewProgram(
			newScreen(shell, out),
			tea.WithInput(in),
			tea.WithOutput(out),
			tea.WithAltScreen(),
		)
		_, _ = program.Run()
		return
	}
	serveLines(stdioSession{in, out}, shell)
}

type stdioSession struct {
	io.Reader
	io.Writer
}

func Serve(session ssh.Session, shell *Shell) {
	_, windowChanges, hasPty := session.Pty()
	if !hasPty {
		serveLines(session, shell)
		return
	}

	model := newScreen(shell, session)
	program := tea.NewProgram(
		model,
		tea.WithInput(session),
		tea.WithOutput(session),
		tea.WithAltScreen(),
	)
	go func() {
		for window := range windowChanges {
			program.Send(tea.WindowSizeMsg{Width: window.Width, Height: window.Height})
		}
	}()
	_, _ = program.Run()
}

func serveLines(session io.ReadWriter, shell *Shell) {
	fmt.Fprint(session, shell.Banner())
	reader := bufio.NewReader(session)
	for {
		fmt.Fprint(session, shell.Prompt())
		line, err := reader.ReadString('\n')
		if err != nil {
			return
		}
		text, quit := shell.Execute(strings.TrimRight(line, "\r\n"))
		fmt.Fprint(session, text)
		if quit {
			return
		}
	}
}
