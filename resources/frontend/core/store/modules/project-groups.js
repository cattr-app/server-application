import ProjectGroupsService from '@/services/resource/project-groups.service';

const service = new ProjectGroupsService();

const state = {
    perPage: 15,
    groups: {},
    groupIdsPerQuery: {},
};

const getters = {
    groups: state => {
        const groups = new Map();
        for (const key in state.groupIdsPerQuery) {
            groups.set(
                key,
                state.groupIdsPerQuery[key].map(id => state.groups[id]),
            );
        }

        return groups;
    },
};

const mutations = {
    setPerPage(s, perPage) {
        s.perPage = perPage;
    },
    setGroups(s, groups) {
        s.groups = groups;
    },
    setGroupIdsPerQuery(s, { query, groupIds }) {
        s.groupIdsPerQuery = { ...s.groupIdsPerQuery, [query]: groupIds };
    },
    resetGroupIdsPerQuery(s) {
        s.groupIdsPerQuery = {};
    },
};

const actions = {
    async loadGroups({ state, commit }, { query, page }) {
        if (
            typeof state.groupIdsPerQuery[query] !== 'undefined' &&
            state.groupIdsPerQuery[query].length > page * state.perPage
        ) {
            return;
        }

        const { data, pagination } = await service.getWithFilters({
            search: { query, fields: ['name'] },
            page: page + 1,
        });

        const groups = { ...state.groups };
        const groupIds = typeof state.groupIdsPerQuery[query] !== 'undefined' ? [...state.groupIdsPerQuery[query]] : [];
        for (const group of data) {
            const { id } = group;
            groups[id] = group;

            if (groupIds.indexOf(id) === -1) {
                groupIds.push(id);
            }
        }

        commit('setPerPage', pagination.perPage);
        commit('setGroups', groups);
        commit('setGroupIdsPerQuery', { query, groupIds });
    },

    resetGroups({ commit }) {
        commit('setGroups', {});
        commit('resetGroupIdsPerQuery');
    },

    init() {
        // TODO
    },
};

export default {
    state,
    getters,
    mutations,
    actions,
};
