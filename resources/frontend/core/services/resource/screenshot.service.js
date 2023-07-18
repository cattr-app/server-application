import ResourceService from '@/services/resource.service';
import axios from 'axios';

export default class ScreenshotService extends ResourceService {
    /**
     * @param id
     * @returns string
     */
    getItemRequestUri(id) {
        return `screenshots/show?id=${id}`;
    }

    /**
     * @param id
     * @returns {Promise<AxiosResponse<T>>}
     */
    getItem(id) {
        return axios.get(this.getItemRequestUri(id));
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     */
    getAll() {
        return axios.get('screenshots/list');
    }

    /**
     * @param filters
     * @param config
     * @returns {Promise<AxiosResponse<T>>}
     */
    getWithFilters(filters, config = {}) {
        return axios.post('screenshots/list', filters, config);
    }

    /**
     * @param id
     * @returns {Promise<AxiosResponse<T>>}
     */
    deleteItem(id) {
        return axios.post('screenshots/remove', { id });
    }
}
