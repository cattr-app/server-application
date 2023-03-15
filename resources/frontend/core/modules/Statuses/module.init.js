export const ModuleConfig = {
    routerPrefix: 'settings',
    loadOrder: 10,
    moduleName: 'Statuses',
};

export function init(context, router) {
    context.addCompanySection(require('./sections/statuses').default(context, router));

    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}
