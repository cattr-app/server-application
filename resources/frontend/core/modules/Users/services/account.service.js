import axios from '@/config/app';
import SettingsService from '@/services/settings.service';
import { store } from '@/store';
import i18n from '@/i18n';
import moment from 'moment';

/**
 * Section service class.
 * Used to fetch data from api for inside DynamicSettings.vue
 * Data is stored inside store -> settings -> sections -> data
 */
export default class AccountService extends SettingsService {
    /**
     * API endpoint URL
     * @returns string
     */
    getItemRequestUri() {
        return `/auth/me`;
    }

    /**
     * Fetch item data from api endpoint
     * @returns {data}
     */
    getAll() {
        let user = store.getters['user/user'];
        if (Object.keys(user).length) {
            Promise.resolve().then(() => {
                return {
                    data: {
                        id: user.id,
                        password: user.password,
                        email: user.email,
                        full_name: user.full_name,
                        user_language: user.user_language,
                    },
                };
            });
        }

        return axios.get(this.getItemRequestUri(), { ignoreCancel: true }).then(({ data }) => {
            const values = data.data;
            return {
                id: values.id,
                password: values.password,
                email: values.email,
                full_name: values.full_name,
                user_language: values.user_language,
            };
        });
    }

    /**
     * Save item data
     * @param data
     * @returns {Promise<void>}
     */
    save(data) {
        i18n.locale = data.user_language;
        moment.locale(data.user_language);
        return axios.post('users/edit', data).then(({ data }) => {
            return {
                data: {
                    id: data.res.id,
                    password: data.res.password,
                    email: data.res.email,
                    full_name: data.res.full_name,
                    user_language: data.res.user_language,
                },
            };
        });
    }
}
