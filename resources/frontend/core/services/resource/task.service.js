import ResourceService from '@/services/resource.service';
import axios from 'axios';
import { serialize } from '@/utils/url';

export default class TasksService extends ResourceService {
    /**
     * @returns {Promise<AxiosResponse<T>>}
     */
    getAll() {
        return axios.get('tasks/list');
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
        return `tasks/show?${serialize({ id })}`;
    }

    /**
     * @param userID
     * @returns {Promise<AxiosResponse<T>>}
     */
    getDashboardTasks(userID) {
        return axios.get(`tasks/dashboard?${serialize({ user_id: userID, with: ['project'] })}`);
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param filters
     * @param config
     */
    getWithFilters(filters, config = {}) {
        return axios.post('tasks/list', filters, config);
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param id
     */
    deleteItem(id) {
        return axios.post('tasks/remove', { id });
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param data
     * @param isNew
     */
    save(data, isNew = false) {
        return axios.post(`tasks/${isNew ? 'create' : 'edit'}`, data);
    }

    getOptionLabelKey() {
        return 'task_name';
    }
}
