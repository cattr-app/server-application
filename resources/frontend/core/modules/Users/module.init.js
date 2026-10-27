import en from './locales/en.json';
import ru from './locales/ru.json';
import usersSection from './sections/users';
import accountSection from './sections/account';
export const ModuleConfig = {
    routerPrefix: 'settings',
    loadOrder: 10,
    moduleName: 'Users',
};

export function init(context, router) {
    context.addCompanySection(usersSection(context, router));
    context.addSettingsSection(accountSection);
    context.addUserMenuEntry({
        label: 'navigation.settings',
        icon: 'icon-settings',
        to: {
            name: 'settings.user.account',
        },
    });
    context.addLocalizationData({
        en,
        ru,
    });

    return context;
}
