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

    if (fs.existsSync(path.join(__dirname, '..', 'etc', 'modules.local.json'))) {
        moduleList = merge(moduleList, require(path.join(__dirname, '..', 'etc', 'modules.local.json')));
    }

    Object.keys(moduleList).forEach(moduleName => {
        if (isObject(moduleList[moduleName])) {
            const moduleConfig = moduleList[moduleName];

            if (
                moduleConfig.type === 'package' &&
                (Object.prototype.hasOwnProperty.call(moduleConfig, 'enabled') ? moduleConfig.enabled : true)
            ) {
                fdArray.push(`    () => require('${moduleConfig.ref}'),`);
                console.log(`${moduleName} => added package as static require dependency`);
            }
        }
    });

    fdArray.push('];');

    fs.writeFileSync(path.join(__dirname, '..', 'generated', 'module.require.js'), fdArray.join('\n'));
    console.log('Finished...');
};
