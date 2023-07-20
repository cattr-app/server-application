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
