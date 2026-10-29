const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../resources/frontend/core/utils/requestError.js'), 'utf8');

function load() {
    // The util is an ES module for the webpack build: replace its axios import with a stub and drop `export`.
    const sandbox = {
        axios: { isCancel: error => Boolean(error && error.__CANCEL__) },
    };
    const script = source.replace(/^import axios from 'axios';$/m, '').replace(/^export /gm, '');
    vm.runInNewContext(`${script}\nthis.isHandledRequestError = isHandledRequestError;`, sandbox);
    return sandbox.isHandledRequestError;
}

const isHandledRequestError = load();

test('a canceled request is handled', () => {
    assert.equal(isHandledRequestError({ __CANCEL__: true, message: 'canceled' }), true);
});

test('a response with an error status is handled (reported by the HTTP interceptor)', () => {
    assert.equal(isHandledRequestError({ response: { status: 401 } }), true);
    assert.equal(isHandledRequestError({ response: { status: 500 } }), true);
});

test('a network error without a response is not handled', () => {
    assert.equal(isHandledRequestError({ message: 'Network Error', request: {} }), false);
});

test('a programming error is not handled', () => {
    assert.equal(isHandledRequestError(new TypeError('x is undefined')), false);
    assert.equal(isHandledRequestError(undefined), false);
});
