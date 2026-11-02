#!/usr/bin/env bash
set -euo pipefail

# OCI tags cannot contain SemVer build metadata ('+'), so accept only the
# canonical version or prerelease forms that can be published unchanged.
semver='^v(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(-[0-9A-Za-z-]+(\.[0-9A-Za-z-]+)*)?$'
tag="${GITHUB_REF_NAME:-}"

if [[ ! "${tag}" =~ ${semver} ]]; then
    echo "::error::Unsupported release tag: ${tag} (expected vMAJOR.MINOR.PATCH[-PRERELEASE], without build metadata)" >&2
    exit 1
fi

version="${tag#v}"

# SemVer accepted here is ASCII-only, so character count equals OCI tag length.
if (( ${#version} > 128 )); then
    echo "::error::Release tag exceeds the 128-character OCI image tag limit: ${tag}" >&2
    exit 1
fi

stable=true

if [[ "${version}" == *-* ]]; then
    stable=false
    prerelease="${version#*-}"
    IFS='.' read -r -a identifiers <<< "${prerelease}"
    for identifier in "${identifiers[@]}"; do
        if [[ "${identifier}" =~ ^0[0-9]+$ ]]; then
            echo "::error::Invalid numeric prerelease identifier '${identifier}' in ${tag}" >&2
            exit 1
        fi
    done
fi

# The mutable `latest` aliases may only move to the highest stable version, so
# publishing a patch of an older line does not downgrade them. RELEASE_TAGS
# (newline-separated) overrides the tags read from the repository for tests.
latest=false

if [[ "${stable}" == true ]]; then
    existing_tags="${RELEASE_TAGS-$(git tag --list 'v*' 2>/dev/null)}"
    highest="$(
        printf '%s\n%s\n' "${tag}" "${existing_tags}" |
            grep -E '^v(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$' |
            sort -V |
            tail -n 1
    )"

    if [[ "${tag}" == "${highest}" ]]; then
        latest=true
    fi
fi

{
    printf 'version=%s\n' "${version}"
    printf 'stable=%s\n' "${stable}"
    printf 'latest=%s\n' "${latest}"
    printf 'application_tags<<TAGS\n%s\n' "${version}"
    if [[ "${latest}" == true ]]; then
        printf 'latest\n'
    fi
    printf 'TAGS\n'
} >> "${GITHUB_OUTPUT:?GITHUB_OUTPUT must be set}"

printf 'Release: %s (stable: %s, latest: %s)\n' "${version}" "${stable}" "${latest}"
