#!/usr/bin/env bash
# Calculates the content-addressed tags of the runtime and builder base images
# (runtime-<hash>, builder-<hash>) and writes them to GITHUB_OUTPUT. Shared by
# release.yml, which publishes the images, and container.yml, which must build
# the application against the images that match the checked-out revision.
#
# Expects the *_CONFIG, IMAGE_BUILD_REVISION, OCI_*, *_DESCRIPTION and
# RUNTIME_IMAGE variables from the workflow environment, plus
# RUNTIME_FILES_HASH / BUILDER_FILES_HASH from hashFiles(). It must run in the
# Wolfi SDK container because apko and melange versions are part of the hash.
set -euo pipefail

for file in \
  "${RUNTIME_CONFIG}" \
  "${BUILDER_CONFIG}" \
  "${PHP_CONFIG}" \
  "${CADDY_CONFIG}" \
  "${NODEJS_CONFIG}" \
  "${PEBBLE_CONFIG}" \
  "${COMPOSER_CONFIG}" \
  "${COREPACK_CONFIG}"
do
  if [[ ! -f "${file}" ]]; then
    echo "::error::Required build configuration not found: ${file}"
    exit 1
  fi
done

runtime_files_hash="${RUNTIME_FILES_HASH}"
builder_files_hash="${BUILDER_FILES_HASH}"

if [[ -z "${runtime_files_hash}" ]]; then
  echo "::error::Unable to calculate runtime configuration hash"
  exit 1
fi

if [[ -z "${builder_files_hash}" ]]; then
  echo "::error::Unable to calculate builder configuration hash"
  exit 1
fi

apko_version="$(apko version 2>&1 | tr '\n' ' ')"
melange_version="$(melange version 2>&1 | tr '\n' ' ')"

# Keep the OCI revision of immutable base images tied to the last
# commit that actually changed their build inputs, rather than to
# every unrelated Cattr application release.
runtime_revision="$(
  git log \
    -1 \
    --format='%H' \
    -- \
    "${RUNTIME_CONFIG}" \
    "${PHP_CONFIG}" \
    "${CADDY_CONFIG}" \
    "${PEBBLE_CONFIG}" \
    build/base/rebuild
)"

builder_revision="$(
  git log \
    -1 \
    --format='%H' \
    -- \
    "${BUILDER_CONFIG}" \
    "${PHP_CONFIG}" \
    "${NODEJS_CONFIG}" \
    "${PEBBLE_CONFIG}" \
    "${COMPOSER_CONFIG}" \
    "${COREPACK_CONFIG}" \
    build/base/rebuild
)"

if [[ -z "${runtime_revision}" ]]; then
  echo "::error::Unable to determine runtime source revision"
  exit 1
fi

if [[ -z "${builder_revision}" ]]; then
  echo "::error::Unable to determine builder source revision"
  exit 1
fi

# Static OCI metadata is part of the image identity as changing it
# changes the resulting OCI manifest/config digest.
runtime_hash="$(
  printf '%s\n%s\n%s\n%s\n%s\n%s\n%s\n' \
    "${IMAGE_BUILD_REVISION}" \
    "${apko_version}" \
    "${melange_version}" \
    "${runtime_files_hash}" \
    "${OCI_SOURCE}" \
    "${OCI_VENDOR}" \
    "${RUNTIME_DESCRIPTION}" |
    sha256sum |
    cut -d' ' -f1
)"

builder_hash="$(
  printf '%s\n%s\n%s\n%s\n%s\n%s\n%s\n' \
    "${IMAGE_BUILD_REVISION}" \
    "${apko_version}" \
    "${melange_version}" \
    "${builder_files_hash}" \
    "${OCI_SOURCE}" \
    "${OCI_VENDOR}" \
    "${BUILDER_DESCRIPTION}" |
    sha256sum |
    cut -d' ' -f1
)"

runtime_tag="runtime-${runtime_hash}"
builder_tag="builder-${builder_hash}"

{
  echo "image=${RUNTIME_IMAGE}"
  echo "runtime_tag=${runtime_tag}"
  echo "runtime_revision=${runtime_revision}"
  echo "builder_tag=${builder_tag}"
  echo "builder_revision=${builder_revision}"
} >> "${GITHUB_OUTPUT}"

echo "Runtime image:"
echo "  ${RUNTIME_IMAGE}:${runtime_tag}"

echo "Builder image:"
echo "  ${RUNTIME_IMAGE}:${builder_tag}"
