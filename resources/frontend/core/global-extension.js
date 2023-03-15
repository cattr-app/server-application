import DefaultLayout from '@/layouts/DefaultLayout';
import AuthLayout from '@/layouts/AuthLayout';
import CustomAtModal from '@/components/global/CustomModal/dialog';

import axios from 'axios';

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
}

export default installGlobalComponents;
