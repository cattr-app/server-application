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
import VueLazyload from 'vue-lazyload';
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

routerInit();

const app = new Vue({
    router,
    store,
    i18n,
    render: h => h(App),
}).$mount('#app');

export default app;
