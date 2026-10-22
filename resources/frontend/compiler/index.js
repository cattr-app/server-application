const fs = require('fs'),
    path = require('path'),
    isObject = require('lodash/isObject'),
    merge = require('lodash/merge');

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
                // Package registration is build-time; its activation is runtime-only.
                fdArray.push(`    { name: ${JSON.stringify(moduleName)}, load: () => require(${JSON.stringify(moduleConfig.ref)}) },`);
                console.log(`${moduleName} => added package to the bundle`);
            }
        }
    });

    fdArray.push('];');

    fs.writeFileSync(path.join(__dirname, '..', 'generated', 'module.require.js'), fdArray.join('\n'));
    console.log('Finished...');
};
