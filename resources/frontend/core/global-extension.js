import DefaultLayout from '@/layouts/DefaultLayout';
import AuthLayout from '@/layouts/AuthLayout';
import CustomAtModal from '@/components/global/CustomModal/dialog';
import { reverbConfig } from '@/utils/reverbConfig';

import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const components = {
    DefaultLayout,
    AuthLayout,
    CustomAtModal,
};

function installGlobalComponents(Vue) {
    const reverb = reverbConfig(window.location, {
        host: process.env.MIX_REVERB_HOST,
        path: process.env.MIX_REVERB_PATH,
        port: process.env.MIX_REVERB_FRONTEND_PORT,
    });

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
        ...reverb,
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
