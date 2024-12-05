import storeModule from './storeModule';

export const ModuleConfig = {
    routerPrefix: 'universal-report',
    moduleName: 'UniversalReport',
};

export function init(context) {
    context.addRoute({
        path: '/report/universal',
        name: 'report.universal',
        component: () => import(/* webpackChunkName: "report.universal" */ './views/UniversalReport.vue'),
        meta: {
            auth: true,
        },
        children: [
            {
                path: 'edit/:id',
                name: 'report.universal.edit',
                component: () => import('./views/EditUniversalReport'),
                meta: {
                    auth: true,
                },
            },
            {
                path: 'view/:id',
                name: 'report.universal.view',
                component: () => import('./views/ViewUniversalReport'),
                meta: {
                    auth: true,
                },
            },
            {
                path: 'create',
                name: 'report.universal.create',
                component: () => import('./views/CreateUniversalReport'),
                meta: {
                    auth: true,
                },
            },
        ],
    });

    context.addRoute({
        path: '/report/universal/edit/:id',
        name: 'report.universal.edit',
        component: () => import(/* webpackChunkName: "report.universal" */ './views/EditUniversalReport.vue'),
        meta: {
            auth: true,
        },
    });

    context.addNavbarEntryDropDown({
        label: 'navigation.universal-report',
        section: 'navigation.dropdown.reports',
        to: {
            name: 'report.universal',
        },
    });

    context.addLocalizationData({
        en: require('./locales/en'),
        ru: require('./locales/ru'),
    });

    context.registerVuexModule(storeModule);

    return context;
}
