#!/usr/bin/env bash
# Production build that includes the e2e fixture module Test/Stub (tests/e2e/fixtures/vendor_modules).
# The fixture is copied into resources/frontend/vendor_modules/ only for this build and removed on exit,
# also when the build fails or is interrupted. Afterwards public/dist contains the stub chunk:
# rebuild with `pnpm prod` before running the other suites.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
SRC="$ROOT/tests/e2e/fixtures/vendor_modules/Test"
DEST="$ROOT/resources/frontend/vendor_modules/Test"

if [ -e "$DEST" ]; then
    echo "$DEST already exists; refusing to overwrite it" >&2
    exit 1
fi

cleanup() { rm -rf "$DEST"; }
trap cleanup EXIT INT TERM

cp -R "$SRC" "$DEST"
(cd "$ROOT" && pnpm prod)
