import en from './locales/en.json';
import ru from './locales/ru.json';
import prioritiesSection from './sections/priorities';
export const ModuleConfig = {
    routerPrefix: 'settings',
    loadOrder: 10,
    moduleName: 'Priorities',
};

export function init(context, router) {
    context.addCompanySection(prioritiesSection(context, router));

    context.addLocalizationData({
        en,
        ru,
    });

    return context;
}
