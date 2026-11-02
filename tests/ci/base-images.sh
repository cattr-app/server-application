#!/usr/bin/env bash
set -euo pipefail

# release.yml publishes the base images and container.yml looks them up by the
# same content hash, so everything feeding that hash must be identical in both.

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
release="${root}/.github/workflows/release.yml"
container="${root}/.github/workflows/container.yml"

same() {
    local what="$1" from_release="$2" from_container="$3"
    [[ -n "${from_release}" ]] || { echo "Nothing found in release.yml for: ${what}" >&2; exit 1; }
    [[ "${from_release}" == "${from_container}" ]] || {
        echo "${what} differs between release.yml and container.yml" >&2
        exit 1
    }
}

# Top-level scalar and folded (>-) env entries
env_entry() {
    awk -v key="$2" '
        $0 ~ "^  " key ":" { found = 1; print; next }
        found && /^    / { print; next }
        found { exit }
    ' "$1"
}

for key in RUNTIME_IMAGE RUNTIME_CONFIG BUILDER_CONFIG PHP_CONFIG CADDY_CONFIG NODEJS_CONFIG \
    PEBBLE_CONFIG COMPOSER_CONFIG COREPACK_CONFIG IMAGE_BUILD_REVISION OCI_SOURCE OCI_VENDOR \
    RUNTIME_DESCRIPTION BUILDER_DESCRIPTION; do
    same "env ${key}" "$(env_entry "${release}" "${key}")" "$(env_entry "${container}" "${key}")"
done

hash_inputs() {
    awk -v key="$2" '
        $0 ~ key ": \\$\\{\\{ hashFiles\\(" { found = 1; next }
        found && /\) \}\}/ { exit }
        found { print }
    ' "$1"
}

for key in RUNTIME_FILES_HASH BUILDER_FILES_HASH; do
    same "${key} inputs" "$(hash_inputs "${release}" "${key}")" "$(hash_inputs "${container}" "${key}")"
done

sdk() {
    grep -o 'ghcr.io/wolfi-dev/sdk@sha256:[0-9a-f]\{64\}' "$1" | sort -u
}

same 'Wolfi SDK image' "$(sdk "${release}")" "$(sdk "${container}")"

grep -Fq 'run: bash scripts/base-image-meta.sh' "${release}"
grep -Fq 'run: bash scripts/base-image-meta.sh' "${container}"
grep -Fq 'BUILDER_BASE_IMAGE_TAG=${{ needs.base-images.outputs.builder_tag }}' "${container}"
grep -Fq 'RUNTIME_BASE_IMAGE_TAG=${{ needs.base-images.outputs.runtime_tag }}' "${container}"

echo 'Base image workflow checks passed.'
