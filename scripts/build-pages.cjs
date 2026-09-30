const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');

const repositoryRoot = path.resolve(__dirname, '..');
const outputDirectory = path.join(repositoryRoot, 'pages-dist');
const pagesBaseUrl = (process.env.PAGES_BASE_URL || 'https://cattr-app.github.io/server-application').replace(
    /\/+$/,
    '',
);

const escapeXml = value =>
    value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');

const runPnpm = (args, env = {}) => {
    const result = spawnSync('pnpm', args, {
        cwd: repositoryRoot,
        env: { ...process.env, ...env },
        stdio: 'inherit',
    });

    if (result.error) {
        throw result.error;
    }

    if (result.status !== 0) {
        process.exit(result.status || 1);
    }
};

fs.rmSync(outputDirectory, { force: true, recursive: true });
fs.mkdirSync(path.join(outputDirectory, 'api'), { recursive: true });

runPnpm(['exec', 'apidoc', '-i', 'app', '-t', 'resources/template-apidoc', '-o', path.join(outputDirectory, 'api')]);

for (const section of ['frontend', 'backend']) {
    const packageName = `cattr-docs-${section}`;
    const sectionBaseUrl = pagesBaseUrl ? `${pagesBaseUrl}/${section}` : '';

    runPnpm(['--filter', packageName, 'run', 'sitemap'], { PAGES_BASE_URL: sectionBaseUrl });

    fs.cpSync(path.join(repositoryRoot, 'docs', section, 'docs'), path.join(outputDirectory, section), {
        recursive: true,
    });

    fs.writeFileSync(
        path.join(outputDirectory, section, 'robots.txt'),
        `User-agent: *\nAllow: /\nSitemap: ${pagesBaseUrl}/${section}/sitemap.xml\n`,
    );
}

const sitemapEntries = ['frontend', 'backend']
    .map(section => `<sitemap><loc>${escapeXml(`${pagesBaseUrl}/${section}/sitemap.xml`)}</loc></sitemap>`)
    .join('');
fs.writeFileSync(
    path.join(outputDirectory, 'sitemap_index.xml'),
    `<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">${sitemapEntries}</sitemapindex>`,
);
fs.writeFileSync(
    path.join(outputDirectory, 'robots.txt'),
    `User-agent: *\nAllow: /\nSitemap: ${pagesBaseUrl}/sitemap_index.xml\n`,
);

fs.copyFileSync(path.join(repositoryRoot, 'docs', 'index.html'), path.join(outputDirectory, 'index.html'));
fs.writeFileSync(path.join(outputDirectory, '.nojekyll'), '');

process.stdout.write(`GitHub Pages site built at ${outputDirectory}\n`);
