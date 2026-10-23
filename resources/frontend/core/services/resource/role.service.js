import ResourceService from '@/services/resource.service';
import axios from 'axios';

export default class RoleService extends ResourceService {
    /**
     * Fetch the role list, bypassing cancellation triggered by route changes.
     *
     * @returns {Promise<AxiosResponse<T>>} Response with roles in `data.data`; rejects on request failure.
     */
    getAll() {
        return axios.get('roles/list', { ignoreCancel: true });
    }
}
