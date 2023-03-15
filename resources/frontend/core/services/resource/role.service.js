import ResourceService from '@/services/resource.service';
import axios from 'axios';
import { serialize } from '@/utils/url';

export default class RoleService extends ResourceService {
    constructor(params = {}) {
        super();
        this.params = params;
    }

    /**
     * @param id
     * @returns {string}
     */
    getItemRequestUri(id) {
        return `roles/show?id=${id}`;
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
        return axios.get('roles/list?' + serialize(this.params));
    }
}
