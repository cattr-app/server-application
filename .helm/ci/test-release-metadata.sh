#!/usr/bin/env bash
set -euo pipefail
bash .helm/ci/release-metadata.sh helm/v0.2.0
for tag in v0.2.0 helm/foo helm/v0.2 helm/v0.2.1 helm/v00.2.0 helm/v0.2.0+build; do
  if bash .helm/ci/release-metadata.sh "$tag"; then
    echo "ERROR: invalid/mismatched tag accepted: $tag"
    exit 1
  fi
done
echo "Helm tag validation passed."
