#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
script="${root}/scripts/release-tags.sh"
workflow="${root}/.github/workflows/release.yml"

check_valid() {
    local tag="$1" expected_version="$2" expected_stable="$3" expected_tags="$4"
    local output actual_version actual_stable actual_tags
    output="$(mktemp)"
    GITHUB_REF_NAME="${tag}" GITHUB_OUTPUT="${output}" bash "${script}" > /dev/null

    actual_version="$(sed -n 's/^version=//p' "${output}")"
    actual_stable="$(sed -n 's/^stable=//p' "${output}")"
    actual_tags="$(sed -n '/^application_tags<<TAGS$/,/^TAGS$/ { /^application_tags<<TAGS$/d; /^TAGS$/d; p; }' "${output}")"

    [[ "${actual_version}" == "${expected_version}" ]] || { echo "Wrong version for ${tag}: ${actual_version}" >&2; exit 1; }
    [[ "${actual_stable}" == "${expected_stable}" ]] || { echo "Wrong stability for ${tag}: ${actual_stable}" >&2; exit 1; }
    [[ "${actual_tags}" == "${expected_tags}" ]] || { echo "Wrong image tags for ${tag}: ${actual_tags}" >&2; exit 1; }
    rm -f "${output}"
}

check_invalid() {
    local tag="$1" output
    output="$(mktemp)"
    if GITHUB_REF_NAME="${tag}" GITHUB_OUTPUT="${output}" bash "${script}" >/dev/null 2>&1; then
        echo "Invalid tag accepted: ${tag}" >&2
        exit 1
    fi
    [[ ! -s "${output}" ]] || { echo "Invalid tag produced outputs: ${tag}" >&2; exit 1; }
    rm -f "${output}"
}

check_valid v4.1.0 4.1.0 true $'4.1.0\nlatest'
check_valid v0.0.0 0.0.0 true $'0.0.0\nlatest'
check_valid v4.1.0-rc.1 4.1.0-rc.1 false '4.1.0-rc.1'
check_valid v4.1.0-beta.2 4.1.0-beta.2 false '4.1.0-beta.2'
check_valid v4.1.0-alpha.1 4.1.0-alpha.1 false '4.1.0-alpha.1'
check_valid v4.1.0-1 4.1.0-1 false '4.1.0-1'

# OCI image tags may contain at most 128 characters. Cover both release
# classifications and verify overlong inputs produce no output metadata.
printf -v long_prerelease '%*s' 122 ''
long_prerelease="${long_prerelease// /a}"
check_valid "v1.2.3-${long_prerelease}" "1.2.3-${long_prerelease}" false "1.2.3-${long_prerelease}"
check_invalid "v1.2.3-${long_prerelease}a"

printf -v long_patch '%*s' 124 ''
long_patch="${long_patch// /1}"
check_valid "v1.2.${long_patch}" "1.2.${long_patch}" true "$(printf '1.2.%s\nlatest' "${long_patch}")"
check_invalid "v1.2.${long_patch}1"

for invalid in vfoo v v4.1 v4.1.0.1 v04.1.0 v4.01.0 v4.1.00 \
    v4.1.0- v4.1.0-rc..1 v4.1.0-rc.01 v4.1.0-01 \
    v4.1.0+build.1 v4.1.0-rc.1+build.1; do
    check_invalid "${invalid}"
done

# The integration contract: both mutable base aliases are gated, and the
# application build consumes only the validated version/tag list.
[[ "$(grep -Fc "if: needs.release-metadata.outputs.stable == 'true'" "${workflow}")" -eq 2 ]]
grep -Fq 'tags: ${{ needs.release-metadata.outputs.application_tags }}' "${workflow}"
grep -Fq 'run: bash scripts/release-tags.sh' "${workflow}"

echo 'Release tag classification checks passed.'
