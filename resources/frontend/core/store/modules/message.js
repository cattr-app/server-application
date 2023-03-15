import MessageService from '@/services/message.service';

const state = {
    message: null,
    type: 'info',

    api: Object,

    updateTrigger: null,
};

const getters = {
    message: s => s.message,
    type: s => s.type,

    api: s => s.api,
};

const mutations = {
    setMessage(state, messageData) {
        const { message, type } = messageData;
        state.message = message || null;
        state.type = type || 'info';

        state.updateTrigger = Date.now();
    },

    setServiceObject(state, serviceObject) {
        state.api = serviceObject;
    },
};

const actions = {
    init(context) {
        context.commit('setServiceObject', new MessageService(context));
    },

    setMessage({ commit }, messageData) {
        commit('setMessage', messageData);
    },
};

export default { state, getters, mutations, actions };
