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
    const reverbScheme = process.env.MIX_REVERB_SCHEME ?? window.location.protocol.replace(':', '');
    const reverbFrontendPort =
        process.env.MIX_REVERB_FRONTEND_PORT || window.location.port || (reverbScheme === 'https' ? 443 : 80);

    for (const component in components) {
        if (components[component].name) {
            Vue.component(components[component].name, components[component]);
        }
    }

    Vue.prototype.$CustomModal = CustomAtModal;
    Vue.prototype.$http = axios;
    Vue.prototype.$echo = new Echo({
        broadcaster: 'pusher',
        key: process.env.MIX_REVERB_APP_KEY,
        wsHost: process.env.MIX_REVERB_HOST ?? window.location.hostname,
        wsPath: process.env.MIX_REVERB_PATH ?? '',
        wsPort: reverbFrontendPort,
        wssPort: reverbFrontendPort,
        forceTLS: reverbScheme === 'https',
        disableStats: true,
        enabledTransports: ['ws', 'wss'],
        Pusher,
        cluster: 'eu',
        auth: {
            headers: {
                Authorization: `Bearer ${localStorage.getItem('access_token')}`,
            },
        },
    });
}

export default installGlobalComponents;
