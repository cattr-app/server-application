#!/usr/bin/env node
'use strict';

// Local base-image builder for Cattr. The host only needs Node.js and Podman.
// Melange, apko and Bash run in the Linux Wolfi SDK container.
const fs = require('node:fs');
const path = require('node:path');
const { spawn } = require('node:child_process');

const IMAGE = 'ghcr.io/cattr-app/server-runtime';
const DEFAULT_CACHE_VOLUME = 'cattr-base-build-cache';
const DEFAULT_SDK_IMAGE = 'ghcr.io/wolfi-dev/sdk:latest';

function usage() {
    console.log(`Usage: pnpm run build:base [--builder | --runtime] [--images-only]

Build the Cattr Wolfi base images from this working tree and load them into Podman.
No Bash, Melange, apko or other Unix tools are required on the host.

Options:
  --builder        Build only the builder base image (default: both).
  --runtime        Build only the runtime base image (default: both).
  --images-only    Reuse cached APK packages after changes to apko.yaml.
  -h, --help       Show this help.

Output images:
  ghcr.io/cattr-app/server-runtime:builder-latest
  ghcr.io/cattr-app/server-runtime:runtime-latest

Environment overrides:
  CATTR_SDK_IMAGE           Wolfi SDK image (default: ${DEFAULT_SDK_IMAGE})
  CATTR_BASE_CACHE_VOLUME   APK cache volume (default: ${DEFAULT_CACHE_VOLUME})

The APK cache and signing key remain in a Podman named volume.
Temporary OCI archives are removed after podman load.`);
}

function options(argv) {
    let target = 'all';
    let mode = 'full';
    for (const arg of argv) {
        switch (arg) {
            case '--builder':
                target = 'builder';
                break;
            case '--runtime':
                target = 'runtime';
                break;
            case '--images-only':
                mode = 'images-only';
                break;
            case '-h':
            case '--help':
                return null;
            default:
                throw new Error(`Unknown argument: ${arg}`);
        }
    }
    return { target, mode };
}

function runPodman(args, input) {
    return new Promise((resolve, reject) => {
        const child = spawn('podman', args, {
            stdio: [input === undefined ? 'inherit' : 'pipe', 'inherit', 'inherit'],
            windowsHide: true,
        });
        child.on('error', reject);
        if (input !== undefined) {
            // A failing `podman run` may close stdin early; its exit code
            // still provides the actionable diagnostic in that case.
            child.stdin.on('error', err => {
                if (err.code !== 'EPIPE') reject(err);
            });
            child.stdin.end(input, 'utf8');
        }
        child.on('close', (code, signal) => {
            if (code === 0) resolve();
            else
                reject(
                    new Error(
                        `podman ${args[0]} failed (exit ${code ?? 'unknown'}${signal ? `, signal ${signal}` : ''})`,
                    ),
                );
        });
    });
}

function imageExists(ref) {
    return new Promise((resolve, reject) => {
        const child = spawn('podman', ['image', 'exists', ref], {
            stdio: 'ignore',
            windowsHide: true,
        });
        child.on('error', reject);
        child.on('close', (code, signal) => {
            if (code === 0) resolve(true);
            else if (code === 1) resolve(false);
            else reject(new Error(`podman image exists failed for ${ref} (exit ${code}, signal ${signal ?? '-'})`));
        });
    });
}

// This shell script runs INSIDE the Linux SDK container, never on the host.
const sdkScript = `set -euo pipefail

mode="$1"
target="$2"
build_id="$3"

apk_dir=/cache/packages
private_key=/cache/melange.rsa
public_key=/cache/melange.rsa.pub
ready_file=/cache/packages.sha256

package_configs=(
    build/packages/php83.yaml
    build/packages/nodejs-24.yaml
    build/packages/pebble.yaml
    build/packages/composer.yaml
    build/packages/corepack.yaml
    build/packages/caddy.yaml
)
package_hash="$(sha256sum "\${package_configs[@]}" scripts/build-base-images.cjs | sha256sum | cut -d ' ' -f1)"

if [[ "$mode" == full ]]; then
    # Mark the cache invalid before changing it so failed runs cannot be reused.
    rm -f "$ready_file" "$private_key" "$public_key"
    rm -rf "$apk_dir"
    mkdir -p "$apk_dir"
    melange keygen "$private_key"

    common_args=(
        --runner bubblewrap
        --arch x86_64
        --out-dir "$apk_dir"
        --signing-key "$private_key"
        --generate-index=false
        --namespace cattr
        --license MIT
        --git-repo-url https://github.com/cattr-app/server-application
    )
    # Include commit metadata when a git checkout is available. Uncommitted
    # changes are still included because the current files are bind-mounted.
    if revision="$(git -c safe.directory=/src rev-parse HEAD 2>/dev/null)"; then
        common_args+=(--git-commit "$revision")
    fi

    for config in \\
        build/packages/php83.yaml \\
        build/packages/caddy.yaml \\
        build/packages/nodejs-24.yaml \\
        build/packages/pebble.yaml
    do
        melange build "$config" "\${common_args[@]}"
    done

    melange index \\
        --arch x86_64 \\
        --output "$apk_dir/x86_64/APKINDEX.tar.gz" \\
        --signing-key "$private_key" \\
        "$apk_dir/x86_64/"*.apk

    for config in \\
        build/packages/composer.yaml \\
        build/packages/corepack.yaml
    do
        melange build "$config" \\
            "\${common_args[@]}" \\
            --repository-append "$apk_dir" \\
            --keyring-append "$public_key"
    done

    melange index \\
        --arch x86_64 \\
        --output "$apk_dir/x86_64/APKINDEX.tar.gz" \\
        --signing-key "$private_key" \\
        "$apk_dir/x86_64/"*.apk

    printf '%s\\n' "$package_hash" > "$ready_file"
else
    if [[ ! -f "$ready_file" || ! -f "$public_key" || \\
          ! -f "$apk_dir/x86_64/APKINDEX.tar.gz" ]]; then
        echo "Error: missing APK cache. Run without --images-only first." >&2
        exit 1
    fi
    if [[ "$(cat "$ready_file")" != "$package_hash" ]]; then
        echo "Error: package definitions changed; rebuild without --images-only." >&2
        exit 1
    fi
    echo 'Reusing locally built APK packages.'
fi

case "$target" in
    all) kinds=(builder runtime) ;;
    builder|runtime) kinds=("$target") ;;
    *) echo "Invalid target: $target" >&2; exit 2 ;;
esac

for kind in "\${kinds[@]}"; do
    image="ghcr.io/cattr-app/server-runtime:local-$kind-$build_id"
    echo "Building $image"
    # apko also writes an SBOM relative to its working directory.
    # /src is mounted read-only, so run each build from its own temporary,
    # writable output directory. Keep configs pointing at the source tree.
    mkdir -p "/output/$kind"
    (
        cd "/output/$kind"
        apko build \\
            --arch amd64 \\
            --repository-append "$apk_dir" \\
            --keyring-append "$public_key" \\
            "/src/build/base/$kind/apko.yaml" \\
            "$image" \\
            "/output/$kind.tar"
    )
done
`;

async function main() {
    const parsed = options(process.argv.slice(2));
    if (parsed === null) {
        usage();
        return;
    }
    const { mode, target } = parsed;
    // Resolve relative to the script itself, not the caller's working directory.
    const root = path.resolve(__dirname, '..');
    const required = [
        'build/base/builder/apko.yaml',
        'build/base/runtime/apko.yaml',
        'build/packages/php83.yaml',
        'build/packages/caddy.yaml',
        'build/packages/nodejs-24.yaml',
        'build/packages/pebble.yaml',
        'build/packages/composer.yaml',
        'build/packages/corepack.yaml',
    ];
    for (const file of required) {
        const requiredPath = path.join(root, ...file.split('/'));
        if (!fs.existsSync(requiredPath)) {
            throw new Error(`Missing build input ${requiredPath}; check the repository checkout and script location.`);
        }
    }

    // Create temporary archives in the project tree: Podman Machine on macOS
    // and Windows is much more likely to have this path shared than os.tmpdir().
    const tempDir = fs.mkdtempSync(path.join(root, '.cattr-base-'));
    const buildId = path.basename(tempDir).slice('.cattr-base-'.length);
    const cacheVolume = process.env.CATTR_BASE_CACHE_VOLUME || DEFAULT_CACHE_VOLUME;
    const sdkImage = process.env.CATTR_SDK_IMAGE || DEFAULT_SDK_IMAGE;
    const kinds = target === 'all' ? ['builder', 'runtime'] : [target];
    console.log(`Source: ${root}\nSDK: ${sdkImage}\nTarget: ${target}\nMode: ${mode}`);

    try {
        // Use volume notation recognized by Podman on Windows, macOS and Linux.
        // The source tree is read-only; the output directory must be writable
        // because apko writes SBOM files relative to its working directory.
        await runPodman(
            [
                'run',
                '--rm',
                '-i',
                '--privileged',
                '-v',
                `${root}:/src:ro`,
                '-v',
                `${cacheVolume}:/cache`,
                '-v',
                `${tempDir}:/output`,
                '-w',
                '/src',
                '--entrypoint',
                '/bin/bash',
                sdkImage,
                '-s',
                '--',
                mode,
                target,
                buildId,
            ],
            sdkScript,
        );

        for (const kind of kinds) {
            const intermediate = `${IMAGE}:local-${kind}-${buildId}`;
            const final = `${IMAGE}:${kind}-latest`;
            await runPodman(['load', '--input', path.join(tempDir, `${kind}.tar`)]);
            const suffixed = `${intermediate}-amd64`;
            const loaded = (await imageExists(suffixed))
                ? suffixed
                : (await imageExists(intermediate))
                  ? intermediate
                  : null;
            if (loaded === null) {
                throw new Error(`podman load succeeded but image ${intermediate} is missing`);
            }
            await runPodman(['tag', loaded, final]);
            await runPodman(['rmi', loaded]);
            console.log(`Loaded locally: ${final}`);
        }
        console.log('Done. Temporary archives removed; APK cache stored in Podman volume:', cacheVolume);
    } finally {
        fs.rmSync(tempDir, { recursive: true, force: true, maxRetries: 4, retryDelay: 200 });
    }
}

main().catch(err => {
    console.error(`Error: ${err.message}`);
    process.exitCode = 1;
});
