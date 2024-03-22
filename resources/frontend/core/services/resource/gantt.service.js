import axios from 'axios';
import ResourceService from '@/services/resource.service';

export default class GanttService extends ResourceService {
    getGanttData(projectId) {
        return axios.get(`projects/gantt-data?id=${projectId}`);
    }
    getPhases(projectId) {
        return axios.get(`projects/phases?id=${projectId}`);
    }
}
