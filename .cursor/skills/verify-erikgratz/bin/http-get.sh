#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../../../.." && pwd)"
cd "$ROOT"
PATH_ARG="${1:?path required e.g. /experience}"
BASE="$("$(dirname "$0")/base-url.sh")"
URL="${BASE}${PATH_ARG}"
OUT="$(mktemp)"
# Follow redirects so `/` → `/home` counts as success; print final URL.
CODE="$(curl -sL -o "$OUT" -w '%{http_code} final:%{url_effective}' "$URL" || true)"
STATUS="${CODE%% final:*}"
FINAL="${CODE#*final:}"
echo "GET $URL => $STATUS ($FINAL)"
head -c 500 "$OUT" | tr '\n' ' '
echo
rm -f "$OUT"
[[ "$STATUS" =~ ^2 ]]
