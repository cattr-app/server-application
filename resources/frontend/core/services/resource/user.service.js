import ResourceService from '@/services/resource.service';
import axios from 'axios';
import { serialize } from '@/utils/url';

export default class UsersService extends ResourceService {
    /**
     * @param config
     * @returns {Promise<AxiosResponse<T>>}
     */
    async getAll(config = {}) {
        return (await axios.get('users/list', config)).data.data;
    }

    /**
     * @param id
     * @returns string
     */
    getItemRequestUri(id) {
        return `users/show?${serialize({ id, with: ['role', 'projectsRelation.role'] })}`;
    }

    /**
     * @param id
     * @returns {Promise<AxiosResponse<T>>}
     */
    getItem(id) {
        return axios.get(this.getItemRequestUri(id));
    }

    /**
     * @param data
     * @param isNew
     * @returns {Promise<AxiosResponse<T>>}
     */
    save(data, isNew = false) {
        return axios.post(`users/${isNew ? 'create' : 'edit'}`, data);
    }

    /**
     * @param id
     * @returns {Promise<AxiosResponse<T>>}
     */
    deleteItem(id) {
        return axios.post('users/remove', { id });
    }

    /**
     * @returns string
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
}
