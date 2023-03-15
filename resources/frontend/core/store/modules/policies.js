const state = {
    policies: {},
};

const getters = {};

const mutations = {
    registerPolicies(s, policies = {}) {
        s.policies = {
            ...s.policies,
            ...policies,
        };
    },
};

const actions = {
    registerPolicies({ commit }, policies = {}) {
        commit('registerPolicies', policies);
    },
};

export default {
    state,
    getters,
    mutations,
    actions,
};
