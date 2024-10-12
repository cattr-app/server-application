import ResourceService from '@/services/resource.service';
import axios from 'axios';

export default class TaskActivityService extends ResourceService {
    /**
     * @param id
     * @returns {Promise<AxiosResponse<T>>}
     */
    deleteComment(id) {
        return axios.post('task-comment/remove', { id });
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param data
     */
    saveComment(data) {
        return axios.post('task-comment/create', data);
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param data
     */
    editComment(data) {
        return axios.post('task-comment/edit', data);
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param data
     */
    getActivity(data) {
        return axios.post('tasks/activity', data);
    }
}
