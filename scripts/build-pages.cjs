const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const output = path.join(root, 'pages-dist');

function pnpm(args) {
    const result = spawnSync('pnpm', args, { cwd: root, stdio: 'inherit' });
    if (result.error) throw result.error;
    if (result.status !== 0) process.exit(result.status || 1);
}

// The OpenAPI document is exported by Laravel (dedoc/scramble, needs the dev dependencies and a migrated
// database). Locally: php artisan scramble:export --path=storage/app/tmp/openapi.json
const spec = path.resolve(root, process.env.OPENAPI_SPEC || 'storage/app/tmp/openapi.json');
if (!fs.existsSync(spec)) {
    process.stderr.write(
        'OpenAPI spec not found at ' + spec + '.\n' +
            'Export it first: php artisan scramble:export --path=storage/app/tmp/openapi.json (or set OPENAPI_SPEC).\n',
    );
    process.exit(1);
}

fs.rmSync(output, { force: true, recursive: true });
fs.mkdirSync(path.join(output, 'api'), { recursive: true });
// Structural errors fail the build; warnings (see redocly.yaml) do not.
pnpm(['exec', 'redocly', 'lint', spec]);
pnpm(['exec', 'redocly', 'build-docs', spec, '--output', path.join(output, 'api', 'index.html'), '--title', 'Cattr API', '--disableGoogleFont']);
fs.copyFileSync(spec, path.join(output, 'api', 'openapi.json'));
fs.copyFileSync(path.join(root, 'docs', 'api', 'logo.svg'), path.join(output, 'api', 'logo.svg'));

// The previous Docsify sites are no longer published. Keep only their
// small hash-aware browser redirects for existing external links.
for (const section of ['frontend', 'backend']) {
    fs.mkdirSync(path.join(output, section), { recursive: true });
    fs.copyFileSync(
        path.join(root, 'docs', 'legacy-redirects', section + '.html'),
        path.join(output, section, 'index.html'),
    );
}

fs.writeFileSync(path.join(output, 'robots.txt'), 'User-agent: *\nAllow: /\n');
fs.copyFileSync(path.join(root, 'docs', 'index.html'), path.join(output, 'index.html'));
fs.writeFileSync(path.join(output, '.nojekyll'), '');
process.stdout.write('API reference and compatibility redirects built at ' + output + '\n');
