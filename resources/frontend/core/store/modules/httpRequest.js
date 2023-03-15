import ApiService from '@/services/api';

const state = {
    cancelTokens: [],
};

const getters = {
    cancelTokens(state) {
        return state.cancelTokens;
    },
};

const mutations = {
    addCancelToken(state, token) {
        state.cancelTokens.push(token);
    },
    clearCancelTokens(state) {
        state.cancelTokens = [];
    },
};

const actions = {
    cancelPendingRequests(context) {
        context.state.cancelTokens.forEach(request => {
            if (request.cancel) {
                request.cancel();
            }
        });

        context.commit('clearCancelTokens');
    },
    async getInstallationStatus() {
        const { installed } = await new ApiService().status();
        return installed || false;
    },

    async getCattrStatus() {
        const { cattr } = await new ApiService().status();
        return cattr || false;
    },
};

export default {
    state,
    getters,
    mutations,
    actions,
};
