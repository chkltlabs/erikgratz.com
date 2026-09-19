#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../../../.." && pwd)"
cd "$ROOT"
MARKER="${1:?marker substring required}"
COUNT="$(./vendor/bin/sail artisan tinker --execute="echo App\\Models\\Contact::query()->where('message','like','%'.addcslashes('${MARKER}','\\%_').'%')->count();" 2>/dev/null | tail -n1 | tr -d '[:space:]')"
if [[ "${COUNT:-0}" -lt 1 ]]; then
  echo "assert-contact: no row containing marker: $MARKER" >&2
  exit 1
fi
echo "assert-contact: found $COUNT row(s) for marker=$MARKER"
