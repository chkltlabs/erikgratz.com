#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../../../.." && pwd)"
cd "$ROOT"
PORT="$(grep -E '^APP_PORT=' .env 2>/dev/null | cut -d= -f2- | tr -d '"' || true)"
PORT="${PORT:-2080}"
echo "http://127.0.0.1:${PORT}"
