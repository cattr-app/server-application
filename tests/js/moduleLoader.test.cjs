const assert = require('node:assert/strict');
const EventEmitter = require('node:events');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const frontendCore = path.join(__dirname, '../../resources/frontend/core');

function esmToScript(source) {
    // The loader is an ES module for the webpack build: turn its imports into lookups of
    // injected stubs and strip the export keywords, so the production source runs in a vm context.
    return source
        .replace(/^import (\{[^}]*\}|\w+) from '([^']+)';$/gm, (_line, binding, specifier) => {
            return `const ${binding} = __imports[${JSON.stringify(specifier)}];`;
        })
        .replace(/^export default .*$/m, '')
        .replace(/^export /gm, '');
}

function loadModuleFlagName() {
    const sandbox = {};
    const source = fs.readFileSync(path.join(frontendCore, 'utils/moduleFlagName.js'), 'utf8');
    vm.runInNewContext(`${esmToScript(source)}\nthis.moduleFlagName = moduleFlagName;`, sandbox);
    return sandbox.moduleFlagName;
}

const moduleFlagName = loadModuleFlagName();

class FakeModule {
    constructor(routerPrefix, moduleName) {
        this.routerPrefix = routerPrefix;
        this.moduleName = moduleName;
    }

    getRoutes() {
        return [{ path: `/${this.routerPrefix}` }];
    }
}

function fakeVendorModule(moduleName, initialized) {
    return {
        ModuleConfig: { moduleName },
        init: context => {
            initialized.push(moduleName);
            return context;
        },
    };
}

// requireContext: { [dir]: { [key]: () => module | Promise<module> } }
function loadModuleLoader({ requireContext, packages, enabled }) {
    const errors = [];
    const sandbox = {
        __imports: {
            path,
            '@/arch/module': FakeModule,
            events: EventEmitter,
            'lodash/kebabCase': value => value.toLowerCase(),
            'lodash/sortBy': (list, key) => [...list].sort((a, b) => a[key] - b[key]),
            '_app/generated/module.require': packages,
            '@/utils/moduleFlagName': { moduleFlagName },
        },
        require: {
            context: dir => {
                const entries = requireContext[dir] || {};
                const context = key => entries[key]();
                context.keys = () => Object.keys(entries);
                return context;
            },
        },
        window: { __CATTR_FRONTEND_MODULES__: Object.fromEntries(enabled.map(name => [moduleFlagName(name), true])) },
        process: { env: { NODE_ENV: 'test' } },
        __dirname: frontendCore,
        console: { log() {}, info() {}, error: (...args) => errors.push(args) },
    };

    const source = fs.readFileSync(path.join(frontendCore, 'moduleLoader.js'), 'utf8');
    vm.runInNewContext(`${esmToScript(source)}\nthis.localModuleLoader = localModuleLoader;`, sandbox);

    return { localModuleLoader: sandbox.localModuleLoader, errors };
}

function fakeRouter() {
    const routes = [];
    return { routes, addRoutes: added => routes.push(...added) };
}

test('a module whose chunk fails to load is skipped while the others initialize', async () => {
    const initialized = [];
    const chunkError = new Error('Loading chunk 42 failed.');
    const { localModuleLoader, errors } = loadModuleLoader({
        requireContext: {
            _modules: {
                './AmazingCat/Broken/module.init.js': () => Promise.reject(chunkError),
                './AmazingCat/Working/module.init.js': () =>
                    Promise.resolve(fakeVendorModule('AmazingCat_Working', initialized)),
            },
        },
        packages: [
            { name: 'Vendor_BrokenPackage', load: () => Promise.reject(chunkError) },
            {
                name: 'Vendor_WorkingPackage',
                load: () => Promise.resolve(fakeVendorModule('Vendor_WorkingPackage', initialized)),
            },
        ],
        enabled: ['AmazingCat_Broken', 'AmazingCat_Working', 'Vendor_BrokenPackage', 'Vendor_WorkingPackage'],
    });

    const router = fakeRouter();
    const modules = await localModuleLoader(router);

    assert.deepEqual(Object.keys(modules).sort(), ['AmazingCat_Working', 'Vendor_WorkingPackage']);
    assert.deepEqual(initialized.sort(), ['AmazingCat_Working', 'Vendor_WorkingPackage']);
    assert.equal(router.routes.length, 2);
    assert.deepEqual(
        errors.map(([message, error]) => [message, error]),
        [
            ['Failed to load module AmazingCat_Broken', chunkError],
            ['Failed to load module Vendor_BrokenPackage', chunkError],
        ],
    );
});

test('a package module that loads without the required exports still fails loudly', async () => {
    const { localModuleLoader } = loadModuleLoader({
        requireContext: {},
        packages: [{ name: 'Vendor_Invalid', load: () => Promise.resolve({ init: context => context }) }],
        enabled: ['Vendor_Invalid'],
    });

    await assert.rejects(localModuleLoader(fakeRouter()), /must export ModuleConfig/);
});

test('disabled modules are never fetched', async () => {
    const { localModuleLoader, errors } = loadModuleLoader({
        requireContext: {
            _modules: {
                './AmazingCat/Disabled/module.init.js': () => assert.fail('disabled local module was fetched'),
            },
        },
        packages: [{ name: 'Vendor_Disabled', load: () => assert.fail('disabled package was fetched') }],
        enabled: [],
    });

    assert.deepEqual(Object.keys(await localModuleLoader(fakeRouter())), []);
    assert.deepEqual(errors, []);
});
