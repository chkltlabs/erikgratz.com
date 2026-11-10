#!/bin/sh
# One interactive sudo. After this, Forge deploys run on-deploy.sh with no sudo.
set -eu

if [ "$(id -u)" -ne 0 ]; then
	echo "Run this once from an SSH session: sudo bash terminal/deploy/prepare-once.sh" >&2
	exit 1
fi

owner=${SUDO_USER:-forge}
if ! id "$owner" >/dev/null 2>&1; then
	echo "user $owner does not exist" >&2
	exit 1
fi
if ! getent group terminal >/dev/null 2>&1; then
	echo "group terminal does not exist" >&2
	exit 1
fi

HERE=$(CDPATH= cd -- "$(dirname "$0")" && pwd)
PREFIX=/var/lib/erikgratz-terminal

install -d -m 2775 -o "$owner" -g terminal "$PREFIX" "$PREFIX/bin" "$PREFIX/bingo"
install -m 755 -o root -g root "$HERE/erikgratz-terminal-shell" /usr/local/bin/erikgratz-terminal-shell

if [ -x /usr/local/bin/erikgratz-terminal ] && [ ! -x "$PREFIX/bin/erikgratz-terminal" ]; then
	install -m 755 -o "$owner" -g terminal /usr/local/bin/erikgratz-terminal "$PREFIX/bin/erikgratz-terminal"
fi

if [ ! -f "$PREFIX/bingo/go.php" ] && [ -f "$HERE/../bingo/go.php" ]; then
	cp -R "$HERE/../bingo/." "$PREFIX/bingo/"
	chmod -R a+rX "$PREFIX/bingo"
fi
