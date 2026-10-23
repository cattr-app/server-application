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

{
    printf 'version=%s\n' "${version}"
    printf 'stable=%s\n' "${stable}"
    printf 'application_tags<<TAGS\n%s\n' "${version}"
    if [[ "${stable}" == true ]]; then
        printf 'latest\n'
    fi
    printf 'TAGS\n'
} >> "${GITHUB_OUTPUT:?GITHUB_OUTPUT must be set}"

printf 'Release: %s (stable: %s)\n' "${version}" "${stable}"
