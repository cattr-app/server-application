import { store } from '@/store';
import { getModuleList, ModuleLoaderInterceptor } from '@/moduleLoader';
import { hasRole } from '@/utils/user';

/**
 * Initialising setting parent route
 * beforeEnter route we are check if any sections uploaded
 * If not - initialize them and provide to store
 */

ModuleLoaderInterceptor.on('loaded', router => {
    const modules = Object.values(getModuleList());
    const settingsModules = modules.filter(m => m.moduleInstance.routerPrefix === 'settings');

    function initSettingsSections(to, from, next) {
        store.dispatch('settings/clearSections');
        settingsModules.forEach(m => m.moduleInstance.initSettingsSections());

        next();
    }

    function initCompanySections(to, from, next) {
        store.dispatch('settings/clearSections');
        settingsModules.forEach(m => m.moduleInstance.initCompanySections());

        if (!Object.keys(store.getters['user/user']).length) {
            store.watch(
                () => store.getters['user/user'],
                user => {
                    return hasRole(user, 'admin') ? next() : next({ name: 'forbidden' });
                },
            );
        } else {
            return hasRole(store.getters['user/user'], 'admin') ? next() : next({ name: 'forbidden' });
        }
    }

    const settings = [
        {
            path: '/company',
            redirect: { name: 'Settings.company.general' },
            component: () => import(/* webpackChunkName: "company" */ '../views/Settings/CompanySettings.vue'),
            meta: {
                auth: true,
            },
            beforeEnter: initCompanySections,
            children: settingsModules.reduce(
                (total, m) => [...total, ...m.moduleInstance.getCompanySectionsRoutes()],
                [],
            ),
        },
        {
            path: '/settings',
            redirect: { name: 'Users.settings.account' },
            component: () => import(/* webpackChunkName: "settings" */ '../views/Settings/Settings.vue'),
            meta: {
                auth: true,
            },
            beforeEnter: initSettingsSections,
            children: settingsModules.reduce(
                (total, m) => [...total, ...m.moduleInstance.getSettingSectionsRoutes()],
                [],
            ),
        },
    ];

    router.addRoutes(settings);
});
