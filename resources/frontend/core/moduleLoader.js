import path from 'path';
import Module from '@/arch/module';
import EventEmitter from 'events';
import kebabCase from 'lodash/kebabCase';
import isObject from 'lodash/isObject';
import sortBy from 'lodash/sortBy';
import moduleRequire from '_app/generated/module.require';
import merge from 'lodash/merge';

export const moduleFilter = moduleName => true;
export const config = { moduleFilter };

let moduleCfg = require('_app/etc/modules.config.json');

try {
    moduleCfg = merge(moduleCfg, require(`_app/etc/modules.${process.env.NODE_ENV}.json`));
} catch (e) {
    if (process.env.NODE_ENV === 'development') {
        console.log(`Skip load of modules.${process.env.NODE_ENV}.json`);
    }
}

try {
    moduleCfg = merge(moduleCfg, require('_app/etc/modules.local.json'));
} catch (e) {
    if (process.env.NODE_ENV === 'development') {
        console.log('Skip load of modules.local.json');
    }
}

export const ModuleLoaderInterceptor = new EventEmitter();

const modules = {};

export function localModuleLoader(router) {
    const requireModule = require.context('_modules', true, /module.init.js$/);
    let moduleInitQueue = [];

    requireModule.keys().forEach(fn => {
        const pathData = fn.split('/');
        const moduleVendor = pathData[1];
        const moduleName = pathData[2];
        const fullModuleName =
            moduleName.search(/integration/) !== -1 && moduleName.search(/module/) !== -1
                ? `${moduleVendor}_${moduleName}Module`
                : `${moduleVendor}_${moduleName}`;

        const md = requireModule(fn);
        const moduleInitData = md.ModuleConfig || { enabled: false };

        const moduleEnabled =
            (typeof moduleInitData.enabled !== 'undefined' ? moduleInitData.enabled : false) &&
            (Object.prototype.hasOwnProperty.call(moduleCfg, fullModuleName)
                ? isObject(moduleCfg[fullModuleName])
                    ? (Object.prototype.hasOwnProperty.call(moduleCfg[fullModuleName], 'type')
                          ? moduleCfg[fullModuleName].type === 'local'
                          : false) &&
                      (Object.prototype.hasOwnProperty.call(moduleCfg[fullModuleName], 'enabled')
                          ? moduleCfg[fullModuleName].enabled
                          : false) &&
                      (Object.prototype.hasOwnProperty.call(moduleCfg[fullModuleName], 'ref')
                          ? moduleCfg[fullModuleName].ref === fullModuleName
                          : false)
                    : false
                : false);

        if (moduleEnabled) {
            moduleInitQueue.push({
                module: md,
                order: Object.prototype.hasOwnProperty.call(moduleInitData, 'loadOrder')
                    ? moduleInitData.loadOrder
                    : 999,
                moduleInitData,
                fullModuleName,
                fn,
                type: 'local',
            });
        }
    });

    // Require package modules
    if (moduleRequire.length > 0) {
        moduleRequire.forEach(requireFn => {
            const md = requireFn();

            if (!Object.prototype.hasOwnProperty.call(md, 'ModuleConfig')) {
                throw new Error(
                    `Vendor module cannot be initialized. All vendor modules must export ModuleConfig object property.`,
                );
            }

            if (!Object.prototype.hasOwnProperty.call(md, 'init')) {
                throw new Error(
                    `Vendor module cannot be initialized. All vendor modules must export init function property`,
                );
            }

            const moduleConfig = md.ModuleConfig;
            if (!Object.prototype.hasOwnProperty.call(moduleConfig, 'moduleName')) {
                throw new Error(
                    `Vendor module cannot be initialized. All vendor modules must have a name matching the pattern Vendor_ModuleName`,
                );
            }

            if (
                moduleInitQueue.findIndex(el => {
                    return el.fullModuleName === moduleConfig.moduleName;
                }) === -1
            ) {
                moduleInitQueue.push({
                    module: md,
                    order: Object.prototype.hasOwnProperty.call(moduleConfig, 'loadOrder')
                        ? moduleConfig.loadOrder
                        : 999,
                    moduleInitData: moduleConfig,
                    fullModuleName: moduleConfig.moduleName,
                    type: 'package',
                });
            }
        });
    }

    const internalModule = require.context('_internal', true, /module.init.js$/);

    internalModule.keys().forEach(fn => {
        const pathData = fn.split('/');
        const moduleName = pathData[1];
        const fullModuleName =
            moduleName.search(/integration/) !== -1 && moduleName.search(/module/) !== -1
                ? `${moduleName}Module`
                : `${moduleName}`;

        const md = internalModule(fn);
        const moduleInitData = md.ModuleConfig || { fullModuleName: moduleName };

        moduleInitQueue.push({
            module: md,
            order: Object.prototype.hasOwnProperty.call(moduleInitData, 'loadOrder') ? moduleInitData.loadOrder : 999,
            moduleInitData,
            fullModuleName,
            fn,
            type: 'internal',
        });
    });

    // Sort modules load order
    moduleInitQueue = sortBy(moduleInitQueue, 'order');

    // Initializing modules sync
    moduleInitQueue.forEach(({ module, moduleInitData, fullModuleName, fn = undefined, type = 'unknown' }) => {
        if (!config.moduleFilter(fullModuleName)) {
            return;
        }

        if (process.env.NODE_ENV === 'development') {
            console.log(`Initializing ${type} module ${fullModuleName}...`);
        }

        const moduleInstance = module.init(
            new Module(
                moduleInitData.routerPrefix || kebabCase(fullModuleName),
                moduleInitData.moduleName || fullModuleName,
            ),
            router,
        );

        if (typeof moduleInstance === 'undefined') {
            throw new Error(
                `Error while initializing module ${fullModuleName}: the context must be returned from init() method`,
            );
        }

        modules[fullModuleName] = {
            path: typeof fn !== 'undefined' ? path.resolve(__dirname, '..', 'modules', fn) : 'NODE_PACKAGE',
            moduleInstance: moduleInstance,
        };

        if (process.env.NODE_ENV === 'development') {
            console.info(`${fullModuleName} has been initialized`);
        }
    });

    if (process.env.NODE_ENV === 'development') {
        console.log("All modules has been initialized successfully. You can run 'system.getModuleList()'");

        window.system.getModuleList = getModuleList;
    }

    Object.keys(modules).forEach(m => {
        const mdInstance = modules[m].moduleInstance;
        ModuleLoaderInterceptor.emit(m, mdInstance);
        modules[m].moduleInstance = mdInstance;
        router.addRoutes([...modules[m].moduleInstance.getRoutes()]);
    });

    // All modules loaded successfully
    ModuleLoaderInterceptor.emit('loaded', router);

    return modules;
}

export function getModuleList() {
    return modules;
}
