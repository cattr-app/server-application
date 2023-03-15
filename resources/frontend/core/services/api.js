import StoreService from './store.service';
import axios from 'axios';

export default class ApiService extends StoreService {
    storeNs = 'user';

    constructor(context) {
        super(context);
    }

    /**
     * @param params
     * @returns {Promise<AxiosResponse<T>>}
     */
    async checkConnectionDatabase(params) {
        return await axios.post('setup/database', params);
    }

    async finishSetup(params) {
        return await axios.put('setup/save', params);
    }

    token() {
        return this.context.getters['token'];
    }

    checkApiAuth() {
        return axios
            .get('/auth/me', { ignoreCancel: true })
            .then(({ data }) => {
                this.context.dispatch('setLoggedInStatus', true);
                this.context.dispatch('setUser', data.data);

                return Promise.resolve();
            })
            .catch(() => {
                localStorage.removeItem('access_token');
                this.context.dispatch('forceUserExit');

                return Promise.reject();
            });
    }

    setUserData(user) {
        this.context.dispatch('setUser', user);
    }

    setUserToken(token) {
        if (token) {
            localStorage.setItem('access_token', token);
        } else {
            localStorage.removeItem('access_token');
        }

        this.context.dispatch('setToken', token);
    }

    setLoggedInStatus(status = true) {
        this.context.dispatch('setLoggedInStatus', status);
    }

    isLoggedIn() {
        return this.context.getters.loggedIn;
    }

    attemptLogin(credentials) {
        return axios
            .post('/auth/login', credentials, { ignoreCancel: true })
            .then(({ data }) => {
                this.setUserToken(data.data.access_token);
                this.setUserData(data.data.user);
                this.setLoggedInStatus();

                return Promise.resolve(data);
            })
            .catch(response => {
                return Promise.reject(response);
            });
    }

    attemptDesktopLogin(token) {
        const instance = axios.create();

        instance.defaults.headers.common['Authorization'] = `desktop ${token}`;

        return instance
            .put('/auth/desktop-key', {}, { ignoreCancel: true })
            .then(({ data }) => {
                this.setUserToken(data.access_token);
                this.setUserData(data.user);
                this.setLoggedInStatus();

                return Promise.resolve(data);
            })
            .catch(response => {
                return Promise.reject(response);
            });
    }

    logout() {
        return axios.post('/auth/logout').then(() => {
            this.context.dispatch('forceUserExit');
        });
    }

    async getCompanyData() {
        const { data } = await axios.get('/company-settings', { ignoreCancel: true });

        this.context.dispatch('setCompanyData', data.data);

        return data.data;
    }

    async status() {
        try {
            const { data } = await axios.get('/status', { ignoreCancel: true });
            return data.data;
        } catch (e) {
            return { cattr: false };
        }
    }

    serverUrl = axios.defaults.baseURL;
}
