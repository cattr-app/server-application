export const ModuleConfig = {
    routerPrefix: 'settings',
    loadOrder: 10,
    moduleName: 'Invitations',
};

export function init(context, router) {
    context.addCompanySection(require('./sections/invitations').default(context, router));

    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}
