import RoleService from '@/services/resource/role.service';

const state = {
    roles: [],
};

const getters = {
    roles: s => s.roles,
};

const mutations = {
    setRoles(s, roles) {
        s.roles = roles;
    },
};

const actions = {
    async loadRoles({ dispatch, state }) {
        if (state.roles && state.roles.length) {
            return state.roles;
        }

        const { data } = await new RoleService().getAll();

        dispatch('setRoles', data.data);

        return data.data;
    },

    setRoles({ commit }, roles) {
        commit('setRoles', roles);
    },
};

export default {
    state,
    getters,
    mutations,
    actions,
};
