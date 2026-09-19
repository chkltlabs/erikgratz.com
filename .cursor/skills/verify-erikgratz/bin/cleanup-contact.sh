#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../../../.." && pwd)"
cd "$ROOT"
MARKER="${1:?marker substring required}"
./vendor/bin/sail artisan tinker --execute="echo App\\Models\\Contact::query()->where('message','like','%'.addcslashes('${MARKER}','\\%_').'%')->delete();" >/dev/null
echo "cleanup-contact: deleted rows matching marker=$MARKER"
