const fs = require('fs');
const http = require('http');
const path = require('path');
const assert = require('assert');
const htmlSnapshots = require('html-snapshots');
const minify = require('html-minifier').minify;

require('./sitemap');

const docsBase = path.join(__dirname, '..', 'docs');

if (!fs.existsSync(docsBase) || !fs.existsSync(path.join(docsBase, 'index.html'))) {
    process.stderr.write('No docs dir');
    process.exit(1);
}

const server = http.createServer(function (req, res) {
    const file = req.url.substr(-1) === '/' ? 'index.html' : req.url;

    fs.readFile(path.join(docsBase, file), function (err, data) {
        if (err) {
            res.writeHead(404);
            res.end(JSON.stringify(err));
            return;
        }
        res.writeHead(200);
        res.end(data);
    });
});

server.listen(8080, 'localhost', 100, () => {
    htmlSnapshots.run(
        {
            input: 'sitemap',
            source: path.join(__dirname, 'sitemap_local.xml'),

            protocol: 'http',

            outputDir: path.join(docsBase, 'snapshots'),

            outputDirClean: true,
            selector: '#intro',
            timeout: 100000,
            phantomjsOptions: ['--ssl-protocol=any', '--ignore-ssl-errors=true', '--load-images=false'],
        },
        (err, snapshotsCompleted) => {
            assert.ifError(err);

            snapshotsCompleted.forEach(snapshotFile => {
                const body = fs
                    .readFileSync(snapshotFile)
                    .toString()
                    .replace('<meta name="fragment" content="!">', '')
                    .replace(/<style[^>]*?>(.|\n)*?<\/style>/gi, '')
                    .replace(/<script[^>]*?>(.|\n)*?<\/script>/gi, '');

                const clearBody = minify(body, {
                    conservativeCollapse: true,
                    removeComments: true,
                    removeEmptyAttributes: true,
                    removeEmptyElements: true,
                    collapseWhitespace: true,
                });
                fs.writeFileSync(snapshotFile, clearBody);
            });

            server.close();
        },
    );
});
