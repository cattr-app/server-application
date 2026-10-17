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

fs.rmSync(output, { force: true, recursive: true });
fs.mkdirSync(path.join(output, 'api'), { recursive: true });
pnpm(['exec', 'apidoc', '-i', 'app', '-t', 'resources/template-apidoc', '-o', path.join(output, 'api')]);

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
