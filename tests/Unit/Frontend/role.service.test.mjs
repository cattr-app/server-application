import assert from 'node:assert/strict';
import { registerHooks } from 'node:module';
import { test } from 'node:test';
import axios from 'axios';

// Run with the repository's Node 24 runtime:
// node --test tests/Unit/Frontend/role.service.test.mjs
// Resolve the Webpack alias without building the frontend or changing application imports.
const aliasHook = registerHooks({
    resolve(specifier, context, nextResolve) {
        if (specifier === '@/services/resource.service') {
            return nextResolve(
                new URL('../../../resources/frontend/core/services/resource.service.js', import.meta.url).href,
                context,
            );
        }

        return nextResolve(specifier, context);
    },
});

let RoleService;
try {
    ({ default: RoleService } = await import('../../../resources/frontend/core/services/resource/role.service.js'));
} finally {
    aliasHook.deregister();
}

test('getAll requests the role list once and excludes it from navigation cancellation', t => {
    const request = Promise.resolve({ data: { data: [] } });
    const get = t.mock.method(axios, 'get', () => request);

    const result = new RoleService().getAll();

    assert.equal(get.mock.callCount(), 1);
    assert.deepEqual(get.mock.calls[0].arguments, ['roles/list', { ignoreCancel: true }]);
    assert.strictEqual(result, request, 'the caller must receive the original Axios promise');
});

for (const [name, roles] of [
    [
        'populated',
        [
            { id: 1, name: 'Admin' },
            { id: 2, name: 'User' },
        ],
    ],
    ['empty', []],
]) {
    test(`getAll preserves the complete Axios response for a ${name} role list`, async t => {
        const response = {
            data: { data: roles },
            status: 200,
            statusText: 'OK',
            headers: { 'content-type': 'application/json' },
        };
        t.mock.method(axios, 'get', () => Promise.resolve(response));

        assert.strictEqual(await new RoleService().getAll(), response);
    });
}

for (const [name, error] of [
    [
        'forbidden responses',
        new axios.AxiosError('Forbidden', 'ERR_BAD_REQUEST', undefined, undefined, { status: 403 }),
    ],
    [
        'server failures',
        new axios.AxiosError('Server error', 'ERR_BAD_RESPONSE', undefined, undefined, { status: 500 }),
    ],
    ['network failures', new axios.AxiosError('Network Error', 'ERR_NETWORK')],
    ['explicit cancellation', new axios.CanceledError('Request canceled')],
]) {
    test(`getAll propagates ${name} unchanged despite ignoring navigation cancellation`, async t => {
        t.mock.method(axios, 'get', () => Promise.reject(error));

        await assert.rejects(
            () => new RoleService().getAll(),
            actual => actual === error,
        );
    });
}

test('getAll protects every request when called again while a role request is pending', async t => {
    const firstRequest = Promise.withResolvers();
    const secondRequest = Promise.withResolvers();
    const responses = [{ data: { data: [{ id: 1, name: 'Admin' }] } }, { data: { data: [] } }];
    const get = t.mock.method(axios, 'get', () => firstRequest.promise);
    get.mock.mockImplementationOnce(() => secondRequest.promise, 1);
    const service = new RoleService();

    const first = service.getAll();
    const second = service.getAll();

    assert.equal(get.mock.callCount(), 2);
    for (const call of get.mock.calls) {
        assert.deepEqual(call.arguments, ['roles/list', { ignoreCancel: true }]);
    }
    assert.strictEqual(first, firstRequest.promise);
    assert.strictEqual(second, secondRequest.promise);

    secondRequest.resolve(responses[1]);
    assert.strictEqual(await second, responses[1]);
    firstRequest.resolve(responses[0]);
    assert.strictEqual(await first, responses[0]);
});
