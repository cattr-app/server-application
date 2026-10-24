const fs = require('fs'),
    path = require('path'),
    isObject = require('lodash/isObject'),
    merge = require('lodash/merge');

// Values are embedded into generated JavaScript, so instead of escaping arbitrary input the
// compiler only accepts a strict character set: module keys are identifiers, package references
// are npm package names (optionally scoped, optionally with a subpath) or relative paths.
const MODULE_NAME_PATTERN = /^[A-Za-z0-9_]+$/;
const PACKAGE_REF_PATTERN = /^(?:\.{1,2}\/)?[A-Za-z0-9@][A-Za-z0-9@/._~-]*$/;

const assertSafeModuleName = value => {
    if (typeof value !== 'string' || !MODULE_NAME_PATTERN.test(value)) {
        throw new TypeError(
            `Invalid module name ${JSON.stringify(value)}: expected letters, digits and underscores only`,
        );
    }
    return value;
};

const assertSafePackageRef = (moduleName, value) => {
    if (typeof value !== 'string' || !value.trim()) {
        throw new TypeError(`Invalid package reference for module ${moduleName}: expected a non-empty string`);
    }
    if (!PACKAGE_REF_PATTERN.test(value) || value.includes('/../') || value.endsWith('/..')) {
        throw new TypeError(
            `Invalid package reference for module ${moduleName}: expected an npm package name or a relative path`,
        );
    }
    return value;
};

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
                const name = assertSafeModuleName(moduleName);
                const ref = assertSafePackageRef(name, moduleConfig.ref);
                // Package registration is build-time; its activation is runtime-only.
                // Each package becomes a separate chunk, fetched only when its flag is enabled.
                // Both values passed the allowlist above, so plain single-quoted literals are safe.
                fdArray.push(
                    `    { name: '${name}', load: () => import(/* webpackChunkName: "${toChunkName(name)}" */ '${ref}') },`,
                );
                console.log(`${moduleName} => added package as a lazy-loaded chunk`);
            }
        }
    });

    fdArray.push('];');

    fs.writeFileSync(path.join(__dirname, '..', 'generated', 'module.require.js'), fdArray.join('\n'));
    console.log('Finished...');
};
