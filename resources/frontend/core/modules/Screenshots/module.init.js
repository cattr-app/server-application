import en from './locales/en.json';
import ru from './locales/ru.json';
export const ModuleConfig = {
    routerPrefix: 'screenshots',
    loadOrder: 20,
    moduleName: 'Screenshots',
};

export function init(context) {
    context.addRoute({
        path: '/screenshots',
        name: 'screenshots',
        component: () => import(/* webpackChunkName: "screenshots" */ './views/Screenshots.vue'),
        meta: {
            auth: true,
        },
    });

    context.addNavbarEntry({
        label: 'navigation.screenshots',
        to: {
            name: 'screenshots',
        },
        displayCondition: store => store.getters['screenshots/enabled'],
    });

    context.addLocalizationData({
        en,
        ru,
    });

    return context;
}
