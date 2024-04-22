import axios from 'axios';
import ResourceService from '@/services/resource.service';

export default class GanttService extends ResourceService {
    getGanttData(projectId) {
        return axios.get(`projects/gantt-data?id=${projectId}`);
    }
    getPhases(projectId) {
        return axios.get(`projects/phases?id=${projectId}`);
    }
    createRelation(taskId, relation) {
        return axios.post(`tasks/create-relation`, {
            task_id: taskId,
            related_task_id: relation.taskId,
            relation_type: relation.type,
        });
    }
    removeRelation({ parent_id, child_id }) {
        return axios.post(`tasks/remove-relation`, {
            parent_id,
            child_id,
        });
    }
}
