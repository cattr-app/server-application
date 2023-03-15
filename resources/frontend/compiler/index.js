const fs = require('fs'),
    isObject = require('lodash/isObject'),
    merge = require('lodash/merge');

module.exports = async (api, options) => {
    api.registerCommand('modules:compile', () => {
        console.log('Reading modules config file...');
        const p = api.resolve('app/etc/modules.config.json');
        if (!fs.existsSync(api.resolve('app/generated'))) {
            fs.mkdirSync(api.resolve('app/generated'));
        }
        if (!fs.existsSync(api.resolve(p))) {
            console.error('modules.config.json was not found in [app/etc] folder');
            return undefined;
        }
        let moduleList = require(p);

        let fdArray = ['export default ['];

        if (fs.existsSync(api.resolve(`app/etc/modules.${process.env.NODE_ENV}.json`))) {
            moduleList = merge(moduleList, require(api.resolve(`app/etc/modules.${process.env.NODE_ENV}.json`)));
        }

        if (fs.existsSync(api.resolve('app/etc/modules.local.json'))) {
            moduleList = merge(moduleList, require(api.resolve('app/etc/modules.local.json')));
        }

        Object.keys(moduleList).forEach(moduleName => {
            if (isObject(moduleList[moduleName])) {
                const moduleConfig = moduleList[moduleName];

                if (
                    moduleConfig.type === 'package' &&
                    (moduleConfig.hasOwnProperty('enabled') ? moduleConfig.enabled : true)
                ) {
                    fdArray.push(`    () => require('${moduleConfig.ref}'),`);
                    console.log(`${moduleName} => added package as static require dependency`);
                }
            }
        });

        fdArray.push('];');

        fs.writeFileSync(api.resolve('app/generated/module.require.js'), fdArray.join('\n'));
        console.log('Finished...');
    });
};
