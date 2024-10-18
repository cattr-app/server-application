const state = {
    states: [
        { value: -1, name: 'any' },
        { value: 0, name: 'forbidden' },
        { value: 1, name: 'required' },
        { value: 2, name: 'optional' },
    ],
};

const getters = {
    states: s => s.states.reduce((acc, el) => Object.assign(acc, { [el.name.toLowerCase()]: el.value }), {}),
    envState: (state, getters, rootState, rootGetters) => {
        const { env_screenshots_state: envValue } = rootGetters['user/companyData'];
        return typeof envValue === 'undefined' ? getters.states.any : envValue;
    },
    companyState: (state, getters, rootState, rootGetters) => {
        const { screenshots_state: companyState } = rootGetters['user/companyData'];
        return typeof companyState === 'undefined' ? getters.states.optional : companyState;
    },
    getCompanyStateWithOverrides: (state, getters) => value => {
        const envValue = getters.envState;
        return envValue !== getters.states.any ? envValue : value;
    },
    companyStateWithOverrides: (state, getters) => getters.getCompanyStateWithOverrides(getters.companyState),
    isCompanyStateLocked: (state, getters) => getters.envState !== getters.states.any,
    isProjectStateLocked: (state, getters) => getters.companyStateWithOverrides !== getters.states.optional,
    isUserStateLocked: (state, getters) => getters.companyStateWithOverrides !== getters.states.optional,
    enabled: (state, getters) => getters.companyStateWithOverrides !== getters.states.forbidden,
};

const actions = {
    async init({ dispatch }) {
        //
    },
};

export default {
    state,
    getters,
    actions,
};
