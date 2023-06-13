import ScreenshotService from '@/services/resource/screenshot.service';

const state = {
    states: [],
};

const getters = {
    states: s =>
        s.states.reduce(
            (acc, el) => (el.value < 0 ? acc : Object.assign(acc, { [el.name.toLowerCase()]: el.value })),
            {},
        ),
};

const mutations = {
    setStates(s, states) {
        s.states = states;
    },
};

const actions = {
    async loadStates({ dispatch, state }) {
        if (state.states && state.states.length) {
            return state.states;
        }

        const { data } = await new ScreenshotService().getStates();

        dispatch('setStates', data.data);

        return data.data;
    },

    setStates({ commit }, states) {
        commit('setStates', states);
    },

    async init({ dispatch }) {
        dispatch('loadStates');
    },
};

export default {
    state,
    getters,
    mutations,
    actions,
};
