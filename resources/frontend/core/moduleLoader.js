import path from 'path';
import Module from '@/arch/module';
import EventEmitter from 'events';
import kebabCase from 'lodash/kebabCase';
import sortBy from 'lodash/sortBy';
import moduleRequire from '_app/generated/module.require';
import { moduleFlagName } from '@/utils/moduleFlagName';

export const moduleFilter = moduleName => true;
export const config = { moduleFilter };

// Only explicit environment flags may enable external frontend modules.
// PHP injects these flags into the page before app.js executes.
const enabledModules = window.__CATTR_FRONTEND_MODULES__ || {};
const isExternalModuleEnabled = name => enabledModules[moduleFlagName(name)] === true;

export const ModuleLoaderInterceptor = new EventEmitter();

const modules = {};

const validatePackageModule = md => {
    if (!Object.prototype.hasOwnProperty.call(md, 'ModuleConfig')) {
        throw new Error(
            `Vendor module cannot be initialized. All vendor modules must export ModuleConfig object property.`,
        );
    }

    if (!Object.prototype.hasOwnProperty.call(md, 'init')) {
        throw new Error(`Vendor module cannot be initialized. All vendor modules must export init function property`);
    }

    if (!Object.prototype.hasOwnProperty.call(md.ModuleConfig, 'moduleName')) {
        throw new Error(
            `Vendor module cannot be initialized. All vendor modules must have a name matching the pattern Vendor_ModuleName`,
        );
    }
};

export async function localModuleLoader(router) {
    // 'lazy' mode: every local vendor module is a separate chunk, fetched only when enabled.
    const requireModule = require.context('_modules', true, /module.init.js$/, 'lazy');
    let moduleInitQueue = [];

    const localModules = requireModule.keys().map(fn => {
        const pathData = fn.split('/');
        const moduleVendor = pathData[1];
        const moduleName = pathData[2];
        const fullModuleName =
            moduleName.search(/integration/) !== -1 && moduleName.search(/module/) !== -1
                ? `${moduleVendor}_${moduleName}Module`
                : `${moduleVendor}_${moduleName}`;

        return { fn, fullModuleName };
    });

    const enabledLocalModules = localModules.filter(({ fullModuleName }) => isExternalModuleEnabled(fullModuleName));
    const loadedLocalModules = await Promise.all(enabledLocalModules.map(({ fn }) => requireModule(fn)));

    enabledLocalModules.forEach(({ fn, fullModuleName }, index) => {
        const md = loadedLocalModules[index];
        const moduleInitData = md.ModuleConfig || {};

        moduleInitQueue.push({
            module: md,
            order: Object.prototype.hasOwnProperty.call(moduleInitData, 'loadOrder') ? moduleInitData.loadOrder : 999,
            moduleInitData,
            fullModuleName,
            fn,
            type: 'local',
        });
    });

    // Load enabled package modules; disabled ones are never fetched
    const enabledPackages = moduleRequire.filter(entry => isExternalModuleEnabled(entry.name));
    const loadedPackages = await Promise.all(enabledPackages.map(entry => entry.load()));

    loadedPackages.forEach(md => {
        validatePackageModule(md);

        const moduleConfig = md.ModuleConfig;
        if (moduleInitQueue.findIndex(el => el.fullModuleName === moduleConfig.moduleName) === -1) {
            moduleInitQueue.push({
                module: md,
                order: Object.prototype.hasOwnProperty.call(moduleConfig, 'loadOrder') ? moduleConfig.loadOrder : 999,
                moduleInitData: moduleConfig,
                fullModuleName: moduleConfig.moduleName,
                type: 'package',
            });
        }
    });

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
        if (type !== 'internal' && !config.moduleFilter(fullModuleName)) {
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
