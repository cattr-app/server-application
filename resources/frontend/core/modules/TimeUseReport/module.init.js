import en from './locales/en.json';
import ru from './locales/ru.json';
export const ModuleConfig = {
    routerPrefix: 'time-use-report',
    loadOrder: 40,
    moduleName: 'TimeUseReport',
};

export function init(context) {
    context.addRoute({
        path: '/report/time-use',
        name: 'report.time-use',
        component: () => import(/* webpackChunkName: "report.timeuse" */ './views/TimeuseReport.vue'),
        meta: {
            auth: true,
        },
    });

    context.addNavbarEntryDropDown({
        label: 'navigation.time-use-report',
        section: 'navigation.dropdown.reports',
        to: {
            name: 'report.time-use',
        },
    });

    context.addLocalizationData({
        en,
        ru,
    });

    return context;
}
