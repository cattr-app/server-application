import Vue from 'vue';
import Vuex from 'vuex';
import modules from './modules';

Vue.use(Vuex);

export const store = new Vuex.Store({
    modules,
    strict: process.env.NODE_ENV !== 'production',
});

export const init = () => {
    for (const moduleName of Object.keys(modules)) {
        if (Object.prototype.hasOwnProperty.call(modules[moduleName].actions, 'init')) {
            store.dispatch(`${moduleName}/init`);
        }
    }
};
