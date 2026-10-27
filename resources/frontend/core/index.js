import '@/config/app';

import { localModuleLoader } from '@/moduleLoader';
import '@/settings';

import Vue from 'vue';
import App from '@/App.vue';
import router from '@/router';
import { store, init as routerInit } from '@/store';
import AtComponents from '@amazingcat/cattr-ui';
import DatePicker from 'vue2-datepicker';
import i18n from '@/i18n';
import '@/plugins/vee-validate';
import '@/policies';
import Gate from '@/plugins/gate';
import vueKanban from 'vue-kanban';
import { Workbox } from 'workbox-window';

//Global components
import installGlobalComponents from './global-extension';

Vue.config.productionTip = false;

Vue.use(AtComponents);
Vue.use(DatePicker);
Vue.use(Gate);
Vue.use(vueKanban);

installGlobalComponents(Vue);

if (process.env.NODE_ENV === 'development') {
    window.system = {};
}

let app;

async function bootstrap() {
    // External modules are fetched as separate chunks, so routes are registered asynchronously.
    await localModuleLoader(router);

    if ('serviceWorker' in navigator) {
        const wb = new Workbox('/service-worker.js');

        wb.register();
    }

    routerInit();

    app = new Vue({
        router,
        store,
        i18n,
        render: h => h(App),
    }).$mount('#app');

    return app;
}

// Resolves with the root instance; a failed module load rejects (and surfaces as an unhandled rejection).
export const appReady = bootstrap();

// Live binding: populated once the bootstrap has mounted the root instance.
export { app as default };
