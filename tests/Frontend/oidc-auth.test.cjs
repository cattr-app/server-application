const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');

async function service(response, reject = false) {
    const calls = [];
    const storage = new Map();
    const actions = [];
    const http = {
        defaults: { headers: { common: {} } },
        post: async (...args) => {
            calls.push(['post', ...args]);
            if (reject) throw new Error('Request denied');
            return { data: response };
        },
        put: async (...args) => {
            calls.push(['put', ...args]);
            return { data: response };
        },
    };
    const axios = {
        defaults: { baseURL: 'https://cattr.example.test/api/' },
        create: options => {
            calls.push(['create', options]);
            return http;
        },
        post: http.post,
    };
    class StoreService {
        constructor(context) { this.context = context; }
    }
    const context = vm.createContext({
        window: { location: { origin: 'https://cattr.example.test' } },
        localStorage: {
            setItem: (key, value) => storage.set(key, value),
            removeItem: key => storage.delete(key),
        },
    });
    const module = new vm.SourceTextModule(
        fs.readFileSync(path.join(__dirname, '../../resources/frontend/core/services/api.js'), 'utf8'),
        { context },
    );
    await module.link(specifier => new vm.SyntheticModule(['default'], function () {
        assert.ok(['axios', './store.service'].includes(specifier));
        this.setExport('default', specifier === 'axios' ? axios : StoreService);
    }, { context }));
    await module.evaluate();
    return {
        api: new module.namespace.default({ dispatch: (...args) => actions.push(args), getters: {} }),
        calls, storage, actions,
    };
}

test('OIDC exchanges a session grant through a same-origin POST, then accepts the normal API envelope', async () => {
    const fixture = { access_token: 'test-token', user: { id: 7, role_id: 2 } };
    const { api, calls, storage, actions } = await service({ data: fixture });
    await api.attemptOidcLogin();
    assert.equal(calls[0][1].baseURL, 'https://cattr.example.test');
    assert.equal(calls[0][1].withCredentials, true);
    assert.equal(calls[1][0], 'post');
    assert.equal(calls[1][1], '/auth/oidc/token');
    assert.deepEqual(Object.keys(calls[1][2]), []);
    assert.equal(storage.get('access_token'), 'test-token');
    assert.ok(actions.some(([name, value]) => name === 'setUser' && value.id === 7 && value.role_id === 2));
});

test('a failed exchange does not mark a browser as logged in or store a token', async () => {
    const { api, storage, actions } = await service({}, true);
    await assert.rejects(api.attemptOidcLogin());
    assert.equal(storage.size, 0);
    assert.equal(actions.length, 0);
});

for (const wrapped of [true, false]) {
    test(`desktop login accepts the ${wrapped ? 'current wrapped' : 'legacy flat'} API response`, async () => {
        const fixture = { access_token: 'test-desktop-token', user: { id: 7 } };
        const { api, storage } = await service(wrapped ? { data: fixture } : fixture);
        await api.attemptDesktopLogin('test-desktop-key');
        assert.equal(storage.get('access_token'), 'test-desktop-token');
    });
}

test('existing password login still consumes the unchanged API envelope', async () => {
    const { api, storage } = await service({ data: { access_token: 'test-password-token', user: { id: 7 } } });
    await api.attemptLogin({ email: 'employee@example.test', password: 'test-only-password' });
    assert.equal(storage.get('access_token'), 'test-password-token');
});
