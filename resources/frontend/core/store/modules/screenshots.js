const state = {
    states: [
        { value: -1, name: 'any' },
        { value: 0, name: 'forbidden' },
        { value: 1, name: 'required' },
        { value: 2, name: 'optional' },
    ],
};

const getters = {
    clipStates: s =>
        s.states.reduce(
            (acc, el) => (el.value < 0 ? acc : Object.assign(acc, { [el.name.toLowerCase()]: el.value })),
            {},
        ),
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
