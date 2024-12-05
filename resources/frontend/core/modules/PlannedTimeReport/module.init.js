export const ModuleConfig = {
    routerPrefix: 'planned-time-report',
    loadOrder: 40,
    moduleName: 'PlannedTimeReport',
};

export function init(context) {
    context.addRoute({
        path: '/report/planned-time',
        name: 'report.planned-time',
        component: () => import(/* webpackChunkName: "report.plannedtime" */ './views/PlannedTimeReport.vue'),
        meta: {
            auth: true,
        },
    });

    context.addNavbarEntryDropDown({
        label: 'navigation.planned-time-report',
        section: 'navigation.dropdown.reports',
        to: {
            name: 'report.planned-time',
        },
    });

    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}
