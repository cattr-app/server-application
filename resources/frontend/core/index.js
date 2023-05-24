import '@/config/app';

import { localModuleLoader } from '@/moduleLoader';
import '@/settings';

import Vue from 'vue';
import App from '@/App.vue';
import router from '@/router';
import { store, init as routerInit } from '@/store';
import AtComponents from '@cattr/ui-kit';
import DatePicker from 'vue2-datepicker';
import moment from 'vue-moment';
import i18n from '@/i18n';
import VueLazyload from 'vue-lazyload';
import '@/plugins/vee-validate';
import '@/policies';
import Gate from '@/plugins/gate';
import vueKanban from 'vue-kanban';
import * as Sentry from '@sentry/vue';
import { BrowserTracing } from '@sentry/tracing';
import { Workbox } from 'workbox-window';

//Global components
import installGlobalComponents from './global-extension';

Vue.config.productionTip = false;

Vue.use(AtComponents);
Vue.use(moment);
Vue.use(DatePicker);
Vue.use(VueLazyload, {
    lazyComponent: true,
});
Vue.use(Gate);
Vue.use(vueKanban);

installGlobalComponents(Vue);

if (process.env.NODE_ENV === 'development') {
    window.system = {};
}

localModuleLoader(router);

if ('serviceWorker' in navigator) {
    const wb = new Workbox('/service-worker.js');

    wb.register();
}

if (
    process.env.NODE_ENV !== 'development' &&
    'MIX_SENTRY_DSN' in process.env &&
    process.env.MIX_SENTRY_DSN !== 'undefined'
) {
    Sentry.init({
        Vue,
        release: process.env.MIX_APP_VERSION,
        environment: process.env.NODE_ENV,
        dsn: process.env.MIX_SENTRY_DSN,
        integrations: [
            new BrowserTracing({
                routingInstrumentation: Sentry.vueRouterInstrumentation(router),
                tracePropagationTargets: [window.location.origin],
            }),
        ],
        tracesSampleRate: 0.2,
    });

    if ('MIX_DOCKER_VERSION' in process.env && process.env.MIX_DOCKER_VERSION !== 'undefined')
        Sentry.setTag('docker', process.env.MIX_DOCKER_VERSION);
}

routerInit();

const app = new Vue({
    router,
    store,
    i18n,
    render: h => h(App),
}).$mount('#app');

export default app;
