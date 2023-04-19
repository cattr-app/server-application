export const ModuleConfig = {
    routerPrefix: 'project-groups',
    loadOrder: 20,
    moduleName: 'ProjectGroups',
};

export function init(context) {
    context.addRoute({
        path: '/groups',
        name: 'projects.groups',
        component: () => import(/* webpackChunkName: "project-groups" */ './views/ProjectGroups.vue'),
        meta: {
            auth: true,
        },
    });

    context.addNavbarEntry({
        label: 'navigation.project-groups',
        to: {
            name: 'projects.groups',
        },
    });

    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}
