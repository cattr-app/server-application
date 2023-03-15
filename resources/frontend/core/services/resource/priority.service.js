import ResourceService from '@/services/resource.service';
import axios from 'axios';
import { serialize } from '@/utils/url';

export default class PriorityService extends ResourceService {
    /**
     * @param config
     * @returns {Promise<AxiosResponse<T>>}
     */
    async getAll(config = {}) {
        return (await axios.get('priorities/list', config)).data.data;
    }

    /**
     * @param id
     * @returns string
     */
    getItemRequestUri(id) {
        return `priorities/show?${serialize({ id })}`;
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
        return axios.post(`priorities/${isNew ? 'create' : 'edit'}`, data);
    }

    /**
     * @param id
     * @returns {Promise<AxiosResponse<T>>}
     */
    deleteItem(id) {
        return axios.post('priorities/remove', { id });
    }

    /**
     * @returns string
     */
    getOptionLabelKey() {
        return 'name';
    }

    /**
     *
     * @param filters
     * @param config
     * @returns {Promise<AxiosResponse<T>>}
     */
    async getWithFilters(filters, config = {}) {
        return (await axios.post('priorities/list', filters, config)).data;
    }
}
