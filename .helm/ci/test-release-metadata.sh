#!/usr/bin/env bash
set -euo pipefail
tags="$(mktemp)"
trap 'rm -f "$tags"' EXIT
printf '%s\n' v4.0.2 v4.1.1 v4.2.0 v4.10.0 v4.11.0-rc.1 v4.12.0-beta.1 v4.13.0-alpha.2 v4.14.0+build.5 helm/v0.2.0 v3.9.9 >"$tags"
export RELEASE_TAGS_FILE="$tags"

# Chart.yaml in git must stay a placeholder.
grep -qx 'version: 0.0.0' .helm/Chart.yaml
grep -qx 'appVersion: "0.0.0"' .helm/Chart.yaml

out="$(bash .helm/ci/release-metadata.sh helm/v0.2.0)"
# 4.10.0 beats 4.2.0 (SemVer, not lexical); the pre-release 4.11.0-rc.1 is ignored.
[[ "$out" == $'chart_version=0.2.0\napp_version=4.10.0' ]] || { echo "ERROR: unexpected output: $out"; exit 1; }
out="$(bash .helm/ci/release-metadata.sh helm/v1.0.0-rc.1)"
[[ "$out" == *"chart_version=1.0.0-rc.1"* ]] || { echo "ERROR: pre-release chart tag rejected"; exit 1; }

for tag in helm/v1.2.3-rc.1+build.7 helm/v1.0.0+20260101 helm/v1.0.0-0.3.7 helm/v1.0.0-x-y.z; do
  bash .helm/ci/release-metadata.sh "$tag" >/dev/null || { echo "ERROR: valid SemVer tag rejected: $tag"; exit 1; }
done
for tag in v0.2.0 helm/foo helm/v0.2 helm/v0.2.0.1 helm/v00.2.0 helm/v1.0.0-01 helm/v1.0.0- helm/v1.0.0+ helm/v1.0.0-a..b; do
  if bash .helm/ci/release-metadata.sh "$tag" >/dev/null 2>&1; then
    echo "ERROR: invalid tag accepted: $tag"
    exit 1
  fi
done

: >"$tags"
if bash .helm/ci/release-metadata.sh helm/v0.2.0 >/dev/null 2>&1; then
  echo "ERROR: accepted a repository without stable releases"
  exit 1
fi
echo "Helm release metadata tests passed."
