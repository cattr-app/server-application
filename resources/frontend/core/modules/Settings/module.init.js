export const ModuleConfig = {
    routerPrefix: 'settings',
    loadOrder: 10,
    moduleName: 'Settings',
};

export function init(context, router) {
    const sectionGeneral = require('./sections/general');
    context.addCompanySection(sectionGeneral.default);
    context.addUserMenuEntry({
        label: 'navigation.company_settings',
        icon: 'icon-settings',
        to: {
            name: 'company.settings.general',
        },
        displayCondition: store => store.getters['user/user'].is_admin === 1,
    });
    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}
