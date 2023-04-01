import ResourceService from '@/services/resource.service';
import axios from 'axios';
import { serialize } from '@/utils/url';

export default class StatusService extends ResourceService {
    /**
     * @param config
     * @returns {Promise<AxiosResponse<T>>}
     */
    async getAll(config = {}) {
        return (await axios.get('statuses/list', config)).data.data;
    }

    /**
     * @param id
     * @returns string
     */
    getItemRequestUri(id) {
        return `statuses/show?${serialize({ id })}`;
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
        return axios.post(`statuses/${isNew ? 'create' : 'edit'}`, data);
    }

    /**
     * @param id
     * @returns {Promise<AxiosResponse<T>>}
     */
    deleteItem(id) {
        return axios.post('statuses/remove', { id });
    }

    /**
     * @returns string
     */
    getOptionLabelKey() {
        return 'name';
    }

    getOptionList() {
        // TODO: this probably will throw :(
        return this.getAll().then(({ data }) =>
            data.data.map(option => ({
                value: option.id,
                label: option[this.getOptionLabelKey()],
                active: option.active,
            })),
        );
    }

    /**
     *
     * @param filters
     * @param config
     * @returns {Promise<AxiosResponse<T>>}
     */
    getWithFilters(filters, config = {}) {
        return axios.post('statuses/list', filters, config);
    }
}
