// Validate repository-local links in Markdown developer guides without Docsify.
const fs = require('node:fs');
const path = require('node:path');

const repo = path.resolve(__dirname, '..');
const root = path.join(repo, 'docs', 'developer');
const failures = [];
let checked = 0;

function visit(dir) {
    for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
        const file = path.join(dir, ent.name);
        if (ent.isDirectory()) { visit(file); continue; }
        if (!ent.isFile() || !ent.name.endsWith('.md')) continue;
        const content = fs.readFileSync(file, 'utf8').replace(/^`{3}[\s\S]*?^`{3}/gm, '');
        for (const match of content.matchAll(/!?\[[^\]]+\]\(([^)]+)\)/g)) {
            const ref = match[1].split(' ')[0].replace(/^<|>$/g, '');
            if (/^(?:https?:|mailto:|#)/i.test(ref)) continue;
            const local = decodeURIComponent(ref.split(/[?#]/)[0]);
            const target = path.resolve(path.dirname(file), local || '.');
            const expected = local.endsWith('/') ? path.join(target, 'README.md') : target;
            if (!fs.existsSync(expected) || !fs.statSync(expected).isFile()) {
                failures.push(path.relative(repo, file) + ': ' + ref);
            }
            checked++;
        }
    }
}
visit(root);
if (failures.length) {
    process.stderr.write('Broken developer guide links:\n' + failures.join('\n') + '\n');
    process.exit(1);
}
process.stdout.write('Checked ' + checked + ' relative developer guide links.\n');
