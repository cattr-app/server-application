import en from './locales/en.json';
import ru from './locales/ru.json';
export const ModuleConfig = {
    routerPrefix: 'calendar',
    loadOrder: 30,
    moduleName: 'Calendar',
};

export function init(context) {
    context.addRoute({
        path: '/calendar',
        name: context.getModuleRouteName() + '.index',
        component: () => import(/* webpackChunkName: "calendar" */ './views/Calendar.vue'),
    });

    context.addNavbarEntry({
        label: 'navigation.calendar',
        to: { path: '/calendar' },
    });

    context.addLocalizationData({
        en,
        ru,
    });

    return context;
}
