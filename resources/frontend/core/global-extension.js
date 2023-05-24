import DefaultLayout from '@/layouts/DefaultLayout';
import AuthLayout from '@/layouts/AuthLayout';
import CustomAtModal from '@/components/global/CustomModal/dialog';

import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const components = {
    DefaultLayout,
    AuthLayout,
    CustomAtModal,
};

function installGlobalComponents(Vue) {
    for (const component in components) {
        if (components[component].name) {
            Vue.component(components[component].name, components[component]);
        }
    }

    Vue.prototype.$CustomModal = CustomAtModal;
    Vue.prototype.$http = axios;
    Vue.prototype.$echo = new Echo({
        broadcaster: 'pusher',
        key: process.env.MIX_PUSHER_APP_KEY,
        wsHost: process.env.MIX_PUSHER_HOST ?? window.location.hostname,
        wsPath: process.env.MIX_PUSHER_PATH ?? '',
        wsPort: process.env.MIX_PUSHER_PORT ?? 80,
        wssPort: process.env.MIX_PUSHER_PORT ?? 443,
        forceTLS: (process.env.MIX_PUSHER_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        Pusher,
        cluster: 'eu',
    });
}

export default installGlobalComponents;
