const state = {
    langList: {
        en: 'English',
        ru: 'Русский',
        // dk: 'Danish', TODO Currently disabled because we dont have DK translations
    },
};

const getters = {
    /*langList: s => Object.keys(s.langList).map(p => ({
        value: p,
        label: s.langList[p]
    }))*/
    langList: s => s.langList,
};

const mutations = {
    lang(s, { code, label }) {
        s.langList[code] = label;
    },
};

const actions = {
    setLang({ commit }, { code, label }) {
        commit('lang', { code, label });
    },
};

export default {
    state,
    getters,
    mutations,
    actions,
};
