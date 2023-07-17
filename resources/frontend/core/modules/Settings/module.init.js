import { hasRole } from '@/utils/user';
import CompanyService from './services/company.service';
import { ModuleLoaderInterceptor } from '@/moduleLoader';
import { hideScreenshotsInHeader } from '@/utils/screenshots';
import { store } from '@/store';

export const ModuleConfig = {
    routerPrefix: 'settings',
    loadOrder: 10,
    moduleName: 'Settings',
};

export function init(context) {
    ModuleLoaderInterceptor.on('Screenshots', context => {
        if (hideScreenshotsInHeader()) {
            context.navEntries = context.navEntries.filter(el => el.label !== 'navigation.screenshots');
            context.routes = context.routes.filter(el => el.name !== 'screenshots');
        } else {
            new CompanyService().getAll().then(({ screenshots_state, env_screenshots_state }) => {
                let states = store.getters['screenshots/states'];
                if (
                    env_screenshots_state === states['forbidden'] ||
                    (env_screenshots_state === states['any'] && screenshots_state === states['forbidden'])
                ) {
                    context.navEntries = context.navEntries.filter(el => el.label !== 'navigation.screenshots');
                    context.routes = context.routes.filter(el => el.name !== 'screenshots');
                }
            });
        }
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
