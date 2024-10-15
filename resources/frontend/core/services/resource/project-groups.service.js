import ResourceService from '@/services/resource.service';
import axios from 'axios';
import { serialize } from '@/utils/url';

export default class ProjectGroupsService extends ResourceService {
    /**
     * @param config
     * @returns {Promise<AxiosResponse<T>>}
     */
    async getAll(config = {}) {
        return (await axios.get('project-groups/list', config)).data.data;
    }

    /**
     * @param id
     * @returns string
     */
    getItemRequestUri(id) {
        return `project-groups/show?${serialize({ id })}`;
    }

    /**
     * @param id
     * @returns {Promise<AxiosResponse<T>>}
     */
    getItem(id) {
        return axios.get(this.getItemRequestUri(id) + '&' + serialize({ with: ['groupParent'] }));
    }

    /**
     * @param data
     * @param isNew
     * @returns {Promise<AxiosResponse<T>>}
     */
    save(data, isNew = false) {
        return axios.post(`project-groups/${isNew ? 'create' : 'edit'}`, data);
    }

    /**
     * @param id
     * @returns {Promise<AxiosResponse<T>>}
     */
    deleteItem(id) {
        return axios.post('project-groups/remove', { id });
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
        return (await axios.post('project-groups/list', filters, config)).data;
    }
}
