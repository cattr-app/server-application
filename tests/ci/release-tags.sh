#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
script="${root}/scripts/release-tags.sh"
workflow="${root}/.github/workflows/release.yml"

check_valid() {
    local tag="$1" expected_version="$2" expected_stable="$3" expected_latest="$4" expected_tags="$5" existing_tags="${6-}"
    local output actual_version actual_stable actual_latest actual_tags
    output="$(mktemp)"
    RELEASE_TAGS="${existing_tags}" GITHUB_REF_NAME="${tag}" GITHUB_OUTPUT="${output}" bash "${script}" > /dev/null

    actual_version="$(sed -n 's/^version=//p' "${output}")"
    actual_stable="$(sed -n 's/^stable=//p' "${output}")"
    actual_latest="$(sed -n 's/^latest=//p' "${output}")"
    actual_tags="$(sed -n '/^application_tags<<TAGS$/,/^TAGS$/ { /^application_tags<<TAGS$/d; /^TAGS$/d; p; }' "${output}")"

    [[ "${actual_version}" == "${expected_version}" ]] || { echo "Wrong version for ${tag}: ${actual_version}" >&2; exit 1; }
    [[ "${actual_stable}" == "${expected_stable}" ]] || { echo "Wrong stability for ${tag}: ${actual_stable}" >&2; exit 1; }
    [[ "${actual_latest}" == "${expected_latest}" ]] || { echo "Wrong latest for ${tag}: ${actual_latest}" >&2; exit 1; }
    [[ "${actual_tags}" == "${expected_tags}" ]] || { echo "Wrong image tags for ${tag}: ${actual_tags}" >&2; exit 1; }
    rm -f "${output}"
}

check_invalid() {
    local tag="$1" output
    output="$(mktemp)"
    if RELEASE_TAGS= GITHUB_REF_NAME="${tag}" GITHUB_OUTPUT="${output}" bash "${script}" >/dev/null 2>&1; then
        echo "Invalid tag accepted: ${tag}" >&2
        exit 1
    fi
    [[ ! -s "${output}" ]] || { echo "Invalid tag produced outputs: ${tag}" >&2; exit 1; }
    rm -f "${output}"
}

check_valid v4.1.0 4.1.0 true true $'4.1.0\nlatest'
check_valid v0.0.0 0.0.0 true true $'0.0.0\nlatest'
check_valid v4.1.0-rc.1 4.1.0-rc.1 false false '4.1.0-rc.1'
check_valid v4.1.0-beta.2 4.1.0-beta.2 false false '4.1.0-beta.2'
check_valid v4.1.0-alpha.1 4.1.0-alpha.1 false false '4.1.0-alpha.1'
check_valid v4.1.0-1 4.1.0-1 false false '4.1.0-1'

# `latest` follows only the highest stable version: a patch of an older line must not
# take it, a newer release and the first release must, and prereleases never count.
check_valid v4.0.2 4.0.2 true false '4.0.2' $'v4.0.1\nv4.0.2\nv4.1.0'
check_valid v4.1.1 4.1.1 true true $'4.1.1\nlatest' $'v4.0.2\nv4.1.0\nv4.1.1'
check_valid v4.10.0 4.10.0 true true $'4.10.0\nlatest' $'v4.9.0\nv4.10.0'
check_valid v4.1.0 4.1.0 true true $'4.1.0\nlatest' $'v4.1.0-rc.1\nv4.2.0-alpha.1\nv4.0.2'
check_valid v4.1.0 4.1.0 true true $'4.1.0\nlatest'

# Without RELEASE_TAGS the script reads the tags of the current repository. Use a
# throwaway repository with tag refs pointing at the empty tree (no commit or tag
# object, so no signing is involved) and an isolated Git config, and verify that a
# failed lookup stops before any output.
sandbox="$(mktemp -d)"
trap 'rm -rf "${sandbox}"' EXIT
export HOME="${sandbox}" GIT_CONFIG_NOSYSTEM=1 GIT_CEILING_DIRECTORIES="${sandbox}"

git init -q "${sandbox}/repo"
empty_tree="$(git -C "${sandbox}/repo" mktree < /dev/null)"
for existing in v4.0.1 v4.0.2 v4.1.0 v4.2.0-rc.1 v4.9.0 v4.10.0; do
    git -C "${sandbox}/repo" update-ref "refs/tags/${existing}" "${empty_tree}"
done

check_git_lookup() {
    local tag="$1" expected_latest="$2" output
    output="$(mktemp -p "${sandbox}")"
    (cd "${sandbox}/repo" && env -u RELEASE_TAGS GITHUB_REF_NAME="${tag}" GITHUB_OUTPUT="${output}" bash "${script}" > /dev/null)
    [[ "$(sed -n 's/^latest=//p' "${output}")" == "${expected_latest}" ]] || { echo "Wrong latest from repository tags for ${tag}" >&2; exit 1; }
}

check_git_lookup v4.10.0 true
check_git_lookup v4.9.0 false
check_git_lookup v4.0.2 false
check_git_lookup v4.11.0 true
check_git_lookup v4.11.0-rc.1 false

mkdir "${sandbox}/not-a-repo"
failed_output="$(mktemp -p "${sandbox}")"
if (cd "${sandbox}/not-a-repo" && env -u RELEASE_TAGS GITHUB_REF_NAME=v4.1.0 GITHUB_OUTPUT="${failed_output}" bash "${script}" > /dev/null 2>&1); then
    echo 'Release tag lookup failure was ignored' >&2
    exit 1
fi
[[ ! -s "${failed_output}" ]] || { echo 'Failed tag lookup produced outputs' >&2; exit 1; }

# OCI image tags may contain at most 128 characters. Cover both release
# classifications and verify overlong inputs produce no output metadata.
printf -v long_prerelease '%*s' 122 ''
long_prerelease="${long_prerelease// /a}"
check_valid "v1.2.3-${long_prerelease}" "1.2.3-${long_prerelease}" false false "1.2.3-${long_prerelease}"
check_invalid "v1.2.3-${long_prerelease}a"

printf -v long_patch '%*s' 124 ''
long_patch="${long_patch// /1}"
check_valid "v1.2.${long_patch}" "1.2.${long_patch}" true true "$(printf '1.2.%s\nlatest' "${long_patch}")"
check_invalid "v1.2.${long_patch}1"

for invalid in vfoo v v4.1 v4.1.0.1 v04.1.0 v4.01.0 v4.1.00 \
    v4.1.0- v4.1.0-rc..1 v4.1.0-rc.01 v4.1.0-01 \
    v4.1.0+build.1 v4.1.0-rc.1+build.1; do
    check_invalid "${invalid}"
done

# The integration contract: both mutable base aliases are gated, and the
# application build consumes only the validated version/tag list.
[[ "$(grep -Fc "if: needs.release-metadata.outputs.latest == 'true'" "${workflow}")" -eq 2 ]]
grep -Fq 'tags: ${{ needs.release-metadata.outputs.application_tags }}' "${workflow}"
grep -Fq 'run: bash scripts/release-tags.sh' "${workflow}"
grep -Fq 'fetch-tags: true' "${workflow}"

echo 'Release tag classification checks passed.'
