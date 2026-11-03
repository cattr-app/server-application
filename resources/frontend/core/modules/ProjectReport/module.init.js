import en from './locales/en.json';
import ru from './locales/ru.json';
export const ModuleConfig = {
    routerPrefix: 'project-report',
    loadOrder: 40,
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
        en,
        ru,
    });

    return context;
}
