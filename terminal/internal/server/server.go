package server

import (
	"log"

	"erikgratz.com/terminal/internal/site"
	"erikgratz.com/terminal/internal/tui"
	"github.com/charmbracelet/ssh"
	"github.com/charmbracelet/wish"
	gossh "golang.org/x/crypto/ssh"
)

// Config is the rehearsal listener. Production does not use it: OpenSSH stays
// on port 22 and execs this program with --stdio for logins that are not a
// real account.
type Config struct {
	Listen       string
	HostKeyPath  string
	SnapshotURL  string
	SnapshotHost string
	SnapshotFile string
	Token        string
	BingoPHP     string
}

func New(cfg Config) (*ssh.Server, error) {
	server, err := wish.NewServer(
		wish.WithAddress(cfg.Listen),
		wish.WithHostKeyPath(cfg.HostKeyPath),
		wish.WithVersion("erikgratz"),
		wish.WithMiddleware(func(next ssh.Handler) ssh.Handler {
			return func(session ssh.Session) {
				snapshot, problem := loadSnapshot(cfg)
				shell := tui.NewShell(snapshot, site.Client{
					SnapshotURL:  cfg.SnapshotURL,
					SnapshotHost: cfg.SnapshotHost,
					Token:        cfg.Token,
				})
				shell.BingoPHP = cfg.BingoPHP
				if problem != nil {
					shell.Problem = problem.Error()
				}
				tui.Serve(session, shell)
				next(session)
			}
		}),
	)
	if err != nil {
		return nil, err
	}

	server.ServerConfigCallback = func(ssh.Context) *gossh.ServerConfig {
		return &gossh.ServerConfig{NoClientAuth: true}
	}
	server.LocalPortForwardingCallback = func(ssh.Context, string, uint32) bool { return false }
	server.ReversePortForwardingCallback = func(ssh.Context, string, uint32) bool { return false }

	return server, nil
}

func loadSnapshot(cfg Config) (site.Snapshot, error) {
	if cfg.SnapshotFile != "" {
		snapshot, err := site.LoadFile(cfg.SnapshotFile)
		if err != nil {
			log.Printf("snapshot file: %v", err)
			return site.Snapshot{}, err
		}
		return snapshot, nil
	}
	if cfg.SnapshotURL == "" {
		return site.Snapshot{}, nil
	}
	snapshot, err := (site.Client{
		SnapshotURL:  cfg.SnapshotURL,
		SnapshotHost: cfg.SnapshotHost,
		Token:        cfg.Token,
	}).Fetch()
	if err != nil {
		log.Printf("snapshot: %v", err)
		return site.Snapshot{}, err
	}
	return snapshot, nil
}
