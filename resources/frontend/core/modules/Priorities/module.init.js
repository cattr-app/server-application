export const ModuleConfig = {
    routerPrefix: 'settings',
    loadOrder: 10,
    moduleName: 'Priorities',
};

export function init(context, router) {
    context.addCompanySection(require('./sections/priorities').default(context, router));

    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}
