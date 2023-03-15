import ResourceService from '@/services/resource.service';
import axios from 'axios';

export default class UserService extends ResourceService {
    /**
     * Get all users.
     *
     * @param config
     * @returns {Promise<AxiosResponse<T>>}
     */
    getAll(config = {}) {
        return axios.get('users/list', config);
    }

    /**
     * Save user.
     *
     * @param data
     * @param isNew
     * @returns {Promise<AxiosResponse<T>>}
     */
    save(data, isNew = false) {
        return axios.post(`users/${isNew ? 'create' : 'edit'}`, data);
    }

    /**
     * Remove user.
     *
     * @param id
     * @returns {Promise<AxiosResponse<T>>}
     */
    deleteItem(id) {
        return axios.post('users/remove', { id });
    }

    /**
     * Get option label key.
     *
     * @returns {string}
     */
    getOptionLabelKey() {
        return 'full_name';
    }

    /**
     *
     * @param filters
     * @param config
     * @returns {Promise<AxiosResponse<T>>}
     */
    getWithFilters(filters, config = {}) {
        return axios.post('users/list', filters, config);
    }

    /**
     * Send at invitation to the user.
     *
     * @param id
     * @returns {Promise<AxiosResponse<T>>}
     */
    sendInvite(id) {
        return axios.post('users/send-invite', { id });
    }
}
