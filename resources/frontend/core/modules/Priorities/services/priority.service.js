import axios from '@/config/app';
import ResourceService from '@/services/resource.service';

export default class PriorityService extends ResourceService {
    getAll(config = {}) {
        return axios.get('priorities/list', config);
    }

    getItemRequestUri(id) {
        return `priorities/show?id=${id}`;
    }

    getItem(id, filters = {}) {
        return axios.get(this.getItemRequestUri(id));
    }

    save(data, isNew = false) {
        return axios.post(`priorities/${isNew ? 'create' : 'edit'}`, data);
    }

    deleteItem(id) {
        return axios.post('priorities/remove', { id });
    }

    getWithFilters(filters, config = {}) {
        return axios.post('priorities/list', filters, config);
    }
}
