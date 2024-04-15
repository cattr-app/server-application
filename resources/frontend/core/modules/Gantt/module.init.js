export const ModuleConfig = {
    routerPrefix: 'gantt',
    loadOrder: 20,
    moduleName: 'Gantt',
};

export function init(context) {
    context.addRoute({
        path: '/gantt/:id',
        name: context.getModuleRouteName() + '.index',
        component: () => import(/* webpackChunkName: "gantt" */ './views/Gantt.vue'),
    });

    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}
