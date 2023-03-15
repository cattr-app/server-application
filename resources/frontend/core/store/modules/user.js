import ApiService from '@/services/api';

const state = {
    api: null,
    lastLogoutReason: null,
    user: {
        token: null,
        data: {},
        loggedIn: false,
        companyData: {},
    },
};

const getters = {
    user: s => s.user.data,
    token: s => s.user.token,
    loggedIn: s => s.user.loggedIn,
    companyData: s => s.user.companyData,
    apiService: s => s.api,
    lastLogoutReason: s => s.lastLogoutReason,
};

const mutations = {
    setService(s, serviceObject) {
        s.api = serviceObject;
    },

    setUserData(s, userData) {
        s.user.data = userData;
    },

    setUserToken(s, token) {
        s.user.token = token;
    },

    setLoggedInStatus(s, status) {
        s.user.loggedIn = status;
    },

    setCompanyData(s, companyData) {
        s.user.companyData = companyData;
    },

    lastLogoutReason(s, reason) {
        s.lastLogoutReason = reason;
    },
};

const actions = {
    init(ctx) {
        if (localStorage.getItem('access_token')) {
            ctx.commit('setUserToken', localStorage.getItem('access_token'));
            ctx.commit('setLoggedInStatus', true);
        }

        if (localStorage.getItem('lastLogoutReason')) {
            ctx.commit('lastLogoutReason', localStorage.getItem('lastLogoutReason'));
            localStorage.removeItem('lastLogoutReason');
        }

        ctx.commit('setService', new ApiService(ctx));
    },

    setToken({ commit }, token) {
        commit('setUserToken', token);
    },

    setUser({ commit }, user) {
        commit('setUserData', user);
    },

    setLoggedInStatus({ commit }, status) {
        commit('setLoggedInStatus', status);
    },

    setCompanyData: ({ commit }, data) => {
        commit('setCompanyData', data);
    },

    forceUserExit({ commit }, reason = null) {
        localStorage.clear();
        sessionStorage.clear();

        if (reason) {
            sessionStorage.setItem('lastLogoutReason', reason);
        }

        sessionStorage.setItem('logout', 'true');

        location.reload();
    },
};

export default {
    state,
    getters,
    mutations,
    actions,
};
