const fs = require('fs');
const path = require('path');

const docsBase = path.join(__dirname, '..', 'docs');

if (!fs.existsSync(docsBase)) {
    process.stderr.write('No docs dir');
    process.exit(1);
}

const sitemap = [];

const scan = (currentPath, relativePath, initialPriority) => {
    fs.readdirSync(currentPath).forEach(el => {
        const newPath = path.join(currentPath, el);

        if (fs.lstatSync(newPath).isDirectory()) {
            if (fs.existsSync(path.join(newPath, 'README.md'))) {
                const priority = fs
                    .readFileSync(path.join(newPath, 'README.md'))
                    .toString()
                    .match(/(?<=:priority=).*$/m);

                const date = new Date(fs.lstatSync(path.join(newPath, 'README.md')).mtimeMs);

                sitemap.push({
                    path: `${relativePath}${el}/`,
                    lastModified: `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date
                        .getDate()
                        .toString()
                        .padStart(2, '0')}`,
                    priority: priority ? parseInt(priority[0], 10) / 10 : initialPriority,
                });
            }
            scan(newPath, `${relativePath}${el}/`, initialPriority - 0.1);
        }
    });
};

scan(docsBase, '#/', 1);

const sitemapPath = path.join(docsBase, 'sitemap.xml');
const localSitemap = path.join(__dirname, 'sitemap_local.xml');
const pagesBaseUrl = process.env.PAGES_BASE_URL
    ? process.env.PAGES_BASE_URL.replace(/\/+$/, '')
    : `http${process.env.npm_package_config_https ? 's' : ''}://${process.env.npm_package_config_domain + process.env.npm_package_config_path}`;

fs.writeFileSync(
    sitemapPath,
    '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
);
fs.writeFileSync(
    localSitemap,
    '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
);

sitemap.forEach(el => {
    fs.writeFileSync(
        sitemapPath,
        `<url><loc>${pagesBaseUrl + el.path}</loc><lastmod>${el.lastModified}</lastmod><priority>${el.priority}</priority></url>`,
        { flag: 'a' },
    );

    fs.writeFileSync(
        localSitemap,
        `<url><loc>http://localhost:8080/${el.path}</loc><lastmod>${el.lastModified}</lastmod><priority>${el.priority}</priority></url>`,
        { flag: 'a' },
    );
});

fs.writeFileSync(sitemapPath, '</urlset>', { flag: 'a' });
fs.writeFileSync(localSitemap, '</urlset>', { flag: 'a' });
