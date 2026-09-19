#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../../../.." && pwd)"
cd "$ROOT"
BASE="$("$(dirname "$0")/base-url.sh")"
CHECK_ADMIN=0
if [[ "${1:-}" == "--admin" ]]; then
  CHECK_ADMIN=1
fi

if ! { ./vendor/bin/sail ps 2>/dev/null || true; } | grep -q 'laravel.test'; then
  echo "doctor: laravel.test not running (./vendor/bin/sail up -d)" >&2
  exit 1
fi

CODE="$(curl -s -o /tmp/verify-erikgratz-home.html -w '%{http_code}' "$BASE/home" || true)"
if [[ "$CODE" != "200" ]]; then
  echo "doctor: GET $BASE/home => HTTP $CODE" >&2
  exit 1
fi
if ! grep -qi 'Erik' /tmp/verify-erikgratz-home.html; then
  echo "doctor: /home body missing expected marker" >&2
  exit 1
fi

echo "doctor: ok public $BASE/home ($CODE)"

if [[ "$CHECK_ADMIN" -eq 1 ]]; then
  ACODE="$(curl -s -o /tmp/verify-erikgratz-admin.html -w '%{http_code}' "$BASE/admin/login" || true)"
  if [[ "$ACODE" != "200" ]]; then
    echo "doctor: GET $BASE/admin/login => HTTP $ACODE" >&2
    exit 1
  fi
  if ! grep -qiE 'sign in|email|password|filament' /tmp/verify-erikgratz-admin.html; then
    echo "doctor: /admin/login body missing login markers" >&2
    exit 1
  fi
  echo "doctor: ok admin $BASE/admin/login ($ACODE)"
fi
