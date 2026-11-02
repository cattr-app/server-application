#!/usr/bin/env bash
set -euo pipefail
version="$(sed -n 's/^version:[[:space:]]*//p' .helm/Chart.yaml | head -1)"
bash .helm/ci/release-metadata.sh "helm/v$version"
# Tags that are malformed, or valid SemVer but do not match Chart.yaml.
for tag in "v$version" helm/foo helm/v0.2 "helm/v$version.1" "helm/v0$version" "helm/v$version+build" helm/v999.0.0; do
  if bash .helm/ci/release-metadata.sh "$tag"; then
    echo "ERROR: invalid/mismatched tag accepted: $tag"
    exit 1
  fi
done
echo "Helm tag validation passed."
