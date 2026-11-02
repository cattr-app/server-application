#!/usr/bin/env bash
# Validate independent Helm tags and metadata. Use: release-metadata.sh helm/v0.2.0
set -euo pipefail
tag="${1:?expected Helm release tag}"
if [[ ! "$tag" =~ ^helm/v(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(-([0-9A-Za-z-]+\.)*[0-9A-Za-z-]+)?$ ]]; then
  echo "::error::Invalid Helm release tag: $tag" >&2
  exit 1
fi
version="${tag#helm/v}"
chart_name="$(sed -n 's/^name:[[:space:]]*//p' .helm/Chart.yaml | head -1)"
chart_version="$(sed -n 's/^version:[[:space:]]*//p' .helm/Chart.yaml | head -1)"
app_version="$(sed -n 's/^appVersion:[[:space:]]*//p' .helm/Chart.yaml | tr -d '"' | head -1)"
[[ "$chart_name" == "server" && "$chart_version" == "$version" ]] || {
  echo "::error::Tag $tag does not match server chart version $chart_version" >&2
  exit 1
}
[[ "$app_version" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z.-]+)?$ ]] || {
  echo "::error::Invalid appVersion: $app_version" >&2
  exit 1
}
echo "Chart: $chart_name, version: $chart_version, application: $app_version"
