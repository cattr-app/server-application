import Vue from 'vue';
import axios from 'axios';
import { store } from '@/store';
import has from 'lodash/has';

// Queue for pending queries
let pendingRequests = 0;

// Changes loader bar state
const setLoading = value => {
    if (value) {
        if (pendingRequests === 0) Vue.prototype.$Loading.start();
        pendingRequests++;
    } else {
        setTimeout(() => {
            if (pendingRequests > 0) pendingRequests--;
            if (pendingRequests < 1) Vue.prototype.$Loading.finish();
        }, 300);
    }
};

// Returns access token
const getAuthToken = () => localStorage.getItem('access_token');

// Adds response interceptor
const responseInterceptor = response => {
    setLoading(false);
    return response;
};

// Adds error response interceptor
const responseErrorInterceptor = error => {
    setLoading(false);

    if (!has(error, 'response.status') || (error.request.responseType === 'blob' && error.request.status === 404)) {
        return Promise.reject(error);
    }

    switch (error.response.status) {
        case 401:
            store.getters['user/loggedIn'] && store.dispatch('user/forceUserExit', error.response.data.message);
            break;

        case 503:
            store.getters['user/loggedIn'] && store.dispatch('user/forceUserExit', 'Data reset');
            break;

        default:
            Vue.prototype.$Notify.error({
                title: 'Error',
                message: has(error, 'response.data.error.message')
                    ? error.response.data.error.message
                    : 'Internal server error',
                duration: 5000,
            });
    }

    return Promise.reject(error);
};

// Adds request interceptor
const requestInterceptor = config => {
    setLoading(true);
    return config;
};

// Adds request error interceptor
const requestErrorInterceptor = error => {
    axios.isCancel(error) ? setLoading(false) : Vue.prototype.$Loading.error();
    return Promise.reject(error);
};

// Sets the access token on the request
const authInterceptor = config => {
    config.headers['Authorization'] = `Bearer ${getAuthToken()}`;
    return config;
};

// Save pending request cancel tokens to the store
const pendingRequestsInterceptor = config => {
    if (config.ignoreCancel) {
        return config;
    }

    // Generate cancel token source
    let source = axios.CancelToken.source();

    // Set cancel token on this request
    config.cancelToken = source.token;

    store.commit('httpRequest/addCancelToken', source);

    return config;
};

export default {
    setup() {
        axios.interceptors.response.use(responseInterceptor, responseErrorInterceptor);
        axios.interceptors.request.use(requestInterceptor, requestErrorInterceptor);

        axios.interceptors.request.use(pendingRequestsInterceptor);
        axios.interceptors.request.use(authInterceptor);
    },
};
