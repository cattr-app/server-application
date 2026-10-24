const fs = require('fs'),
    path = require('path'),
    isObject = require('lodash/isObject'),
    merge = require('lodash/merge');

// Escape characters that can be unsafe in generated JavaScript without
// re-escaping quotes or backslashes already handled by JSON.stringify.
const toJsLiteral = value =>
    JSON.stringify(value)
        .replace(/</g, '\\u003C')
        .replace(/>/g, '\\u003E')
        .replace(/\u2028/g, '\\u2028')
        .replace(/\u2029/g, '\\u2029');

// Chunk names end up inside a /* */ comment and in file names: keep only a safe subset.
const toChunkName = value => {
    const safe = String(value)
        .toLowerCase()
        .replace(/[^a-z0-9_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
    return `module-${safe || 'package'}`;
};

module.exports = () => {
    console.log('Reading modules config file...');
    const p = path.join(__dirname, '..', 'etc', 'modules.config.json');

    if (!fs.existsSync(path.join(__dirname, '..', 'generated'))) {
        fs.mkdirSync(path.join(__dirname, '..', 'generated'));
    }
    if (!fs.existsSync(p)) {
        console.error('modules.config.json was not found in [etc] folder');
        return undefined;
    }
    let moduleList = require(p);

    let fdArray = ['export default ['];

    if (fs.existsSync(path.join(__dirname, '..', 'etc', `modules.${process.env.NODE_ENV}.json`))) {
        moduleList = merge(
            moduleList,
            require(path.join(__dirname, '..', 'etc', `modules.${process.env.NODE_ENV}.json`)),
        );
    }

    if (fs.existsSync(path.join(__dirname, '..', 'etc', 'modules.ci.json'))) {
        moduleList = merge(moduleList, require(path.join(__dirname, '..', 'etc', 'modules.ci.json')));
    }

    if (fs.existsSync(path.join(__dirname, '..', 'etc', 'modules.local.json'))) {
        moduleList = merge(moduleList, require(path.join(__dirname, '..', 'etc', 'modules.local.json')));
    }

    Object.keys(moduleList).forEach(moduleName => {
        if (isObject(moduleList[moduleName])) {
            const moduleConfig = moduleList[moduleName];

            if (moduleConfig.type === 'package') {
                const ref = moduleConfig.ref;
                if (typeof ref !== 'string' || !ref.trim()) {
                    throw new TypeError(
                        `Invalid package reference for module ${moduleName}: expected a non-empty string`,
                    );
                }

                // Package registration is build-time; its activation is runtime-only.
                // Each package becomes a separate chunk, fetched only when its flag is enabled.
                fdArray.push(
                    `    { name: ${toJsLiteral(moduleName)}, load: () => import(/* webpackChunkName: "${toChunkName(
                        moduleName,
                    )}" */ ${toJsLiteral(ref)}) },`,
                );
                console.log(`${moduleName} => added package as a lazy-loaded chunk`);
            }
        }
    });

    fdArray.push('];');

    fs.writeFileSync(path.join(__dirname, '..', 'generated', 'module.require.js'), fdArray.join('\n'));
    console.log('Finished...');
};
