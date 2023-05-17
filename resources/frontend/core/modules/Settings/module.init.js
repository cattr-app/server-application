import { hasRole } from '@/utils/user';
import CompanyService from './services/company.service';
import { ModuleLoaderInterceptor } from '@/moduleLoader';

export const ModuleConfig = {
    routerPrefix: 'settings',
    loadOrder: 10,
    moduleName: 'Settings',
};

export function init(context) {
    ModuleLoaderInterceptor.on('Screenshots', context => {
        new CompanyService().getAll().then(({ enable_screenshots }) => {
            if (enable_screenshots === 'forbidden') {
                context.navEntries = context.navEntries.filter(el => el.label !== 'navigation.screenshots');
                context.routes = context.routes.filter(el => el.name !== 'screenshots');
            }
        });
    });

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
