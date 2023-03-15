import ResourceService from '@/services/resource.service';
import axios from 'axios';

export default class TimeIntervalService extends ResourceService {
    /**
     * @returns {string}
     * @param id
     */
    getItemRequestUri(id) {
        return `time-intervals/show?id=${id}`;
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param id
     */
    getItem(id) {
        return axios.get(this.getItemRequestUri(id));
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param filters
     */
    getAll(filters = {}) {
        return axios.post('time-intervals/list', filters);
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param data
     */
    save(data) {
        return axios.post('time-intervals/create', data);
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param data
     */
    bulkEdit(data) {
        return axios.post('time-intervals/bulk-edit', data);
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param data
     */
    bulkDelete(data) {
        return axios.post('time-intervals/bulk-remove', data);
    }

    /**
     * @param id
     * @returns {Promise<AxiosResponse<T>>}
     */
    deleteItem(id) {
        return axios.post('time-intervals/remove', { id });
    }
}
