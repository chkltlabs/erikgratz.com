#!/bin/sh
# Rebuild the SSH terminal from this checkout and install it.
# Forge runs this as root, from any working directory.
set -eu

HERE=$(CDPATH= cd -- "$(dirname "$0")" && pwd)
TERMINAL=$(CDPATH= cd -- "$HERE/.." && pwd)
BINGO="$TERMINAL/bingo/go.php"

if [ ! -f "$BINGO" ]; then
	echo "missing $BINGO" >&2
	exit 1
fi

export PATH="/usr/local/go/bin:$PATH"

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
	rm -rf /usr/local/go
	tar -C /usr/local -xzf "$tmp/$tarball"
	rm -rf "$tmp"
fi

cd "$TERMINAL"
export GOFLAGS=-mod=readonly
built=$(mktemp)
go build -o "$built" ./cmd/erikgratz-terminal
install -m 755 "$built" /usr/local/bin/erikgratz-terminal
rm -f "$built"

cat > /usr/local/bin/erikgratz-terminal-shell << EOF
#!/bin/sh
exec /usr/local/bin/erikgratz-terminal --stdio \\
	--snapshot-url https://erikgratz.com/api/terminal/snapshot \\
	--snapshot-host erikgratz.com \\
	--bingo ${BINGO}
EOF
chmod 755 /usr/local/bin/erikgratz-terminal-shell
