import { hasRole } from '@/utils/user';
import sectionGeneral from './sections/general';
import en from './locales/en.json';
import ru from './locales/ru.json';

export const ModuleConfig = {
    routerPrefix: 'settings',
    loadOrder: 10,
    moduleName: 'Settings',
};

export function init(context) {
    context.addCompanySection(sectionGeneral);
    context.addUserMenuEntry({
        label: 'navigation.company_settings',
        icon: 'icon-settings',
        to: { name: 'company.settings.general' },
        displayCondition: store => hasRole(store.getters['user/user'], 'admin'),
    });
    context.addLocalizationData({
        en,
        ru,
    });

    return context;
}
