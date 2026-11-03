#!/usr/bin/env bash
# Derive the published versions from a Helm release tag.
#   release-metadata.sh helm/v0.2.0
# Chart.yaml carries the placeholder 0.0.0; the real values are computed here:
#   chart_version = the tag without "helm/v" (any valid SemVer 2.0.0)
#   app_version   = the highest stable SemVer among the GitHub releases
# Prints chart_version=... and app_version=... (also appended to $GITHUB_OUTPUT).
# RELEASE_TAGS_FILE (one tag per line) replaces the GitHub API lookup in tests.
set -euo pipefail
tag="${1:?expected Helm release tag}"
# Full SemVer 2.0.0: optional pre-release and build metadata.
id='(0|[1-9][0-9]*|[0-9]*[A-Za-z-][0-9A-Za-z-]*)'
if [[ ! "$tag" =~ ^helm/v(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(-${id}(\.${id})*)?(\+[0-9A-Za-z-]+(\.[0-9A-Za-z-]+)*)?$ ]]; then
  echo "::error::Invalid Helm release tag: $tag" >&2
  exit 1
fi
chart_version="${tag#helm/v}"

chart_name="$(sed -n 's/^name:[[:space:]]*//p' .helm/Chart.yaml | head -1)"
[[ "$chart_name" == "server" ]] || {
  echo "::error::Unexpected chart name: $chart_name" >&2
  exit 1
}

if [[ -n "${RELEASE_TAGS_FILE:-}" ]]; then
  tags="$(cat "$RELEASE_TAGS_FILE")"
else
  tags="$(gh api "repos/${GITHUB_REPOSITORY:?}/releases" --paginate \
    --jq '.[] | select(.draft == false and .prerelease == false) | .tag_name')"
fi
# Stable only: plain vX.Y.Z. Tags with any suffix (alpha, beta, rc, build, ...)
# and non-application tags are skipped, as are releases GitHub marks as pre-release.
app_version="$(grep -E '^v(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$' <<<"$tags" | sed 's/^v//' | sort -V | tail -1 || true)"
[[ -n "$app_version" ]] || {
  echo "::error::No stable application release found" >&2
  exit 1
}

echo "chart_version=$chart_version"
echo "app_version=$app_version"
if [[ -n "${GITHUB_OUTPUT:-}" ]]; then
  echo "chart_version=$chart_version" >>"$GITHUB_OUTPUT"
  echo "app_version=$app_version" >>"$GITHUB_OUTPUT"
fi
