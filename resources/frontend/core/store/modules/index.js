import camelCase from 'lodash/camelCase';

const requireModule = require.context('.', true, /^(?!.*(actions|mutations|getters|index)).*\.js$/);

const modules = {};

requireModule.keys().forEach(fn => {
    if (/\.unit\.js$/.test(fn)) {
        return undefined;
    }

    modules[camelCase(fn.split('/')[1].replace(/(\.\/|\.js)/g, ''))] = {
        namespaced: true,
        ...requireModule(fn).default,
    };
});

export default modules;
