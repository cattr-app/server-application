export const ModuleConfig = {
    routerPrefix: 'project-report',
    loadOrder: 20,
    moduleName: 'ProjectReport',
};

export function init(context) {
    context.addRoute({
        path: '/report/projects',
        name: 'report.projects',
        component: () => import(/* webpackChunkName: "project-report" */ './views/ProjectReport.vue'),
        meta: {
            auth: true,
        },
    });

    context.addNavbarEntryDropDown({
        label: 'navigation.project-report',
        section: 'navigation.dropdown.reports',
        to: {
            name: 'report.projects',
        },
    });

    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}
