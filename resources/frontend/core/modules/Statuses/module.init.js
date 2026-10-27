import en from './locales/en.json';
import ru from './locales/ru.json';
import statusesSection from './sections/statuses';
export const ModuleConfig = {
    routerPrefix: 'settings',
    loadOrder: 10,
    moduleName: 'Statuses',
};

export function init(context, router) {
    context.addCompanySection(statusesSection(context, router));

    context.addLocalizationData({
        en,
        ru,
    });

    return context;
}
