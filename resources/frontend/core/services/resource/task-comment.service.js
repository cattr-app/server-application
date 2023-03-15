import ResourceService from '@/services/resource.service';
import axios from 'axios';
import { serialize } from '@/utils/url';

export default class TaskCommentService extends ResourceService {
    /**
     * @returns {Promise<AxiosResponse<T>>}
     */
    getAll() {
        return axios.get('task-comment/list');
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param id
     * @param filters
     */
    getItem(id, filters = {}) {
        return axios.get(this.getItemRequestUri(id) + '&' + serialize(filters));
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param id
     */
    getItemRequestUri(id) {
        return `task-comment/show?${serialize({ id })}`;
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param filters
     * @param config
     */
    getWithFilters(filters, config = {}) {
        return axios.post('task-comment/list', filters, config);
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param id
     */
    deleteItem(id) {
        return axios.post('task-comment/remove', { id });
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param data
     * @param isNew
     */
    save(data) {
        return axios.post('task-comment/create', data);
    }
}
