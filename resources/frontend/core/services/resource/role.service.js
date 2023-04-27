import ResourceService from '@/services/resource.service';
import axios from 'axios';

export default class RoleService extends ResourceService {
    /**
     * @returns {Promise<AxiosResponse<T>>}
     */
    getAll() {
        return axios.get('roles/list');
    }
}
