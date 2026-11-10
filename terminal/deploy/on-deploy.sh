#!/bin/sh
# Rebuild the SSH terminal from this checkout and install it.
# Forge runs this as the site user, with no sudo.
set -eu

HERE=$(CDPATH= cd -- "$(dirname "$0")" && pwd)
TERMINAL=$(CDPATH= cd -- "$HERE/.." && pwd)
BINGO_SRC="$TERMINAL/bingo"
PREFIX=/var/lib/erikgratz-terminal
BIN_DIR="$PREFIX/bin"
BINGO_DST="$PREFIX/bingo"

if [ ! -f "$BINGO_SRC/go.php" ]; then
	echo "missing $BINGO_SRC/go.php" >&2
	exit 1
fi

if [ ! -w "$BIN_DIR" ] || [ ! -w "$BINGO_DST" ]; then
	echo "Run this once from an SSH session, then deploy again:" >&2
	echo "  sudo bash terminal/deploy/prepare-once.sh" >&2
	exit 1
fi

GO_ROOT="${HOME}/.local/go"
export PATH="$GO_ROOT/bin:$PATH"

if ! command -v go >/dev/null 2>&1; then
	arch=$(uname -m)
	case "$arch" in
		x86_64) goarch=amd64 ;;
		aarch64 | arm64) goarch=arm64 ;;
		*)
			echo "unsupported architecture: $arch" >&2
			exit 1
			;;
	esac
	version=1.23.8
	tarball="go${version}.linux-${goarch}.tar.gz"
	tmp=$(mktemp -d)
	curl -fsSL "https://go.dev/dl/${tarball}" -o "$tmp/$tarball"
	mkdir -p "${HOME}/.local"
	rm -rf "$GO_ROOT"
	tar -C "${HOME}/.local" -xzf "$tmp/$tarball"
	rm -rf "$tmp"
fi

cd "$TERMINAL"
export GOFLAGS=-mod=readonly
export CGO_ENABLED=0
built=$(mktemp)
go build -o "$built" ./cmd/erikgratz-terminal
install -m 755 "$built" "$BIN_DIR/erikgratz-terminal"
rm -f "$built"

if ! find "$BINGO_DST" -mindepth 1 -delete; then
	echo "Bingo files are owned by root. From an SSH session, run:" >&2
	echo "  sudo chown -R $(id -un):terminal $BIN_DIR $BINGO_DST" >&2
	echo "  bash terminal/deploy/on-deploy.sh" >&2
	exit 1
fi
cp -R "$BINGO_SRC/." "$BINGO_DST/"
chmod -R a+rX "$BINGO_DST"
