import { hasRole } from '@/utils/user';

export const ModuleConfig = {
    routerPrefix: 'settings',
    loadOrder: 10,
    moduleName: 'Settings',
};

export function init(context) {
    const sectionGeneral = require('./sections/general');
    context.addCompanySection(sectionGeneral.default);
    context.addUserMenuEntry({
        label: 'navigation.company_settings',
        icon: 'icon-settings',
        to: {
            name: 'company.settings.general',
        },
        displayCondition: store => hasRole(store.getters['user/user'], 'admin'),
    });
    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    return context;
}
