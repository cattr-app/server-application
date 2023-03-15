const state = {
    sections: [],
    additionalFields: [],
};

const getters = {
    sections: s => {
        return s.sections.map(section => {
            const additionalFields = s.additionalFields
                .filter(({ scope, path }) => `${scope}.${path}` === section.pathName)
                .map(({ field }) => field);
            if (additionalFields.length > 0) {
                return {
                    ...section,
                    fields: [...section.fields, ...additionalFields],
                };
            }

            return section;
        });
    },
};

const mutations = {
    setSection(s, section) {
        s.sections.push(section);
    },

    updateSection(s, { section, data }) {
        s.sections = s.sections.map(item => {
            if (item.pathName !== section.pathName) {
                return item;
            }

            return {
                ...item,
                data: {
                    ...item.data,
                    ...data,
                },
            };
        });
    },

    clearSections(s) {
        s.sections = [];
    },

    addField(s, { scope, path, field }) {
        s.additionalFields.push({ scope, path, field });
    },
};

const actions = {
    /**
     * Push new section to sections array
     *
     * @param store
     * @param section
     * @returns {Promise}
     */
    async setSettingSection(store, section) {
        // We need this if when navigating from Settings to Company because store already have values and will not fire action
        if (Object.keys(this.getters['user/user']).length) {
            await addSectionToStore(store, section);
        }

        this.watch(
            () => this.getters['user/user'],
            async () => await addSectionToStore(store, section),
        );
    },

    async updateSection({ commit, state }, { pathName, data }) {
        const section = state.sections.find(section => section.pathName === pathName);
        commit('updateSection', { section, data });
    },

    /**
     * Clear all sections to fill them again with new data
     *
     * @param commit
     */
    clearSections: ({ commit }) => {
        commit('clearSections');
    },

    addField({ commit }, { scope, path, field }) {
        commit('addField', { scope, path, field });
    },
};

export default {
    state,
    getters,
    mutations,
    actions,
};

const addSectionToStore = async (store, section) => {
    const access = await section.accessCheck();
    if (!access || store.state.sections.findIndex(s => s.pathName === section.name) >= 0) {
        return;
    }
    section.meta.service.getAll().then(data => {
        section = {
            label: section.meta.label,
            fields: section.meta.fields,
            pathName: section.name,
            service: section.meta.service,
            access: access,
            scope: section.scope,
            order: section.order,
            data,
        };
        store.commit('setSection', section);
    });
};
