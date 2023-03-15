export const ModuleConfig = {
    routerPrefix: 'settings',
    loadOrder: 10,
    moduleName: 'Users',
};

export function init(context, router) {
    context.addCompanySection(require('./sections/users').default(context, router));
    context.addSettingsSection(require('./sections/account').default);
    context.addUserMenuEntry({
        label: 'navigation.settings',
        icon: 'icon-settings',
        to: {
            name: 'settings.user.account',
        },
    });
    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}
