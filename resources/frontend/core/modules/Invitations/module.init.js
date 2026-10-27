import en from './locales/en.json';
import ru from './locales/ru.json';
import invitationsSection from './sections/invitations';
export const ModuleConfig = {
    routerPrefix: 'settings',
    loadOrder: 10,
    moduleName: 'Invitations',
};

export function init(context, router) {
    context.addCompanySection(invitationsSection(context, router));

    context.addLocalizationData({
        en,
        ru,
    });

    return context;
}
