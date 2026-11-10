package main

import (
	"context"
	"errors"
	"flag"
	"fmt"
	"log"
	"os"
	"os/signal"
	"syscall"
	"time"

	"erikgratz.com/terminal/internal/envfile"
	"erikgratz.com/terminal/internal/server"
	"erikgratz.com/terminal/internal/site"
	"erikgratz.com/terminal/internal/tui"
	"github.com/charmbracelet/ssh"
)

func main() {
	stdio := flag.Bool("stdio", false, "serve the session on stdin and stdout")
	listen := flag.String("listen", "127.0.0.1:2222", "rehearsal address; production is --stdio under OpenSSH")
	hostKey := flag.String("host-key", "host_ed25519", "host private key; created if missing")
	snapshotURL := flag.String("snapshot-url", "http://127.0.0.1/api/terminal/snapshot", "Laravel snapshot URL")
	snapshotHost := flag.String("snapshot-host", "erikgratz.com", "Host header and TLS name for the snapshot request")
	snapshotFile := flag.String("snapshot-file", "", "read the snapshot from a file instead of HTTP")
	envPath := flag.String("env-file", "/etc/erikgratz-terminal.env", "file holding TERMINAL_TOKEN when the environment does not")
	bingo := flag.String("bingo", os.Getenv("BINGO_PHP"), "path to Auto-Bingo go.php")
	token := flag.String("token", os.Getenv("TERMINAL_TOKEN"), "X-Terminal-Token value")
	flag.Parse()

	if *token == "" {
		*token = envfile.Token(*envPath)
	}

	if *stdio {
		snapshot, problem := load(*snapshotFile, *snapshotURL, *snapshotHost, *token)
		shell := tui.NewShell(snapshot, site.Client{
			SnapshotURL:  *snapshotURL,
			SnapshotHost: *snapshotHost,
			Token:        *token,
		})
		shell.BingoPHP = *bingo
		if problem != nil {
			shell.Problem = problem.Error()
		}
		tui.ServeStdio(shell)
		return
	}

	srv, err := server.New(server.Config{
		Listen:       *listen,
		HostKeyPath:  *hostKey,
		SnapshotURL:  *snapshotURL,
		SnapshotHost: *snapshotHost,
		SnapshotFile: *snapshotFile,
		Token:        *token,
		BingoPHP:     *bingo,
	})
	if err != nil {
		log.Fatal(err)
	}

	done := make(chan os.Signal, 1)
	signal.Notify(done, os.Interrupt, syscall.SIGTERM)
	go func() {
		<-done
		ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
		defer cancel()
		if err := srv.Shutdown(ctx); err != nil && !errors.Is(err, ssh.ErrServerClosed) {
			log.Printf("shutdown: %v", err)
		}
	}()

	log.Printf("listening on %s", *listen)
	if err := srv.ListenAndServe(); err != nil && !errors.Is(err, ssh.ErrServerClosed) {
		log.Fatal(err)
	}
	fmt.Fprintln(os.Stderr, "stopped")
}

func load(file, url, host, token string) (site.Snapshot, error) {
	if file != "" {
		snapshot, err := site.LoadFile(file)
		if err != nil {
			log.Printf("snapshot file: %v", err)
			return site.Snapshot{}, err
		}
		return snapshot, nil
	}
	if url == "" {
		return site.Snapshot{}, nil
	}
	snapshot, err := (site.Client{SnapshotURL: url, SnapshotHost: host, Token: token}).Fetch()
	if err != nil {
		log.Printf("snapshot: %v", err)
		return site.Snapshot{}, err
	}
	return snapshot, nil
}
