import ResourceService from '@/services/resource.service';
import axios from 'axios';
import { serialize } from '@/utils/url';

export default class ProjectService extends ResourceService {
    constructor(params = {}) {
        super();
        this.params = params;
    }

    /**
     * @param id
     * @returns {string}
     */
    getItemRequestUri(id) {
        return `projects/show?id=${id}`;
    }

    /**
     * @param id
     * @returns {Promise<AxiosResponse<T>>}
     */
    getItem(id) {
        return axios.get(
            this.getItemRequestUri(id) + '&' + serialize({ with: ['users', 'defaultPriority', 'statuses'] }),
        );
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     */
    async getAll(config = {}) {
        return (
            await axios.get('projects/list', {
                ...config,
                params: this.params,
            })
        ).data.data;
    }

    /**
     * @param id
     * @returns {Promise<AxiosResponse<T>>}
     */
    deleteItem(id) {
        return axios.post('projects/remove', { id });
    }

    /**
     *
     * @param filters
     * @param config
     * @returns {Promise<AxiosResponse<T>>}
     */
    getWithFilters(filters, config = {}) {
        return axios.post('projects/list', filters, config);
    }

    /**
     * @param data
     * @param isNew
     * @returns {Promise<AxiosResponse<T>>}
     */
    save(data, isNew = false) {
        return axios.post(`projects/${isNew ? 'create' : 'edit'}`, data);
    }

    getMembers(id) {
        return axios.post('project-members/list', { project_id: id });
    }

    bulkEditMembers(data) {
        return axios.post('project-members/bulk-edit', data);
    }

    /**
     *
     * @returns {string}
     */
    getOptionLabelKey() {
        return 'name';
    }
}
