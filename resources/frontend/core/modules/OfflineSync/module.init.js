export const ModuleConfig = {
    routerPrefix: 'offline-sync',
    loadOrder: 11,
    moduleName: 'OfflineSync',
};

export function init(context) {
    context.addUserMenuEntry({
        label: 'navigation.offline_sync',
        icon: 'icon-wifi-off',
        to: {
            name: 'offline-sync',
        },
    });
    context.addRoute({
        path: '/offline-sync',
        name: 'offline-sync',
        component: () => import(/* webpackChunkName: "offline-sync" */ './components/Page.vue'),
        meta: {
            auth: true,
        },
    });

    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}
