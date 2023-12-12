import ReportService from '@/services/report.service';
import axios from 'axios';

export default class UniversalReportService extends ReportService {
    constructor(taskService, projectService, userService) {
        super();
        this.taskService = taskService;
        this.projectService = projectService;
        this.userService = userService;
    }

    getBases() {
        return axios.get('report/universal-report/bases');
    }

    getDataObjectsAndFields(base) {
        return axios.get(`report/universal-report/data-objects-and-fields?base=${base}`);
    }

    getReports() {
        return axios.get('report/universal-report');
    }

    create(data) {
        return axios.post('report/universal-report', data);
    }

    show(id) {
        return axios.get(`report/universal-report/show?id=${id}`);
    }

    edit(id, data) {
        return axios.post(`report/universal-report/edit?id=${id}`, data);
    }

    generate(id, data) {
        return axios.post(`report/universal-report/generate?id=${id}`, data);
    }

    deleteItem(id) {
        return axios.post('report/universal-report/remove', { id });
    }

    downloadReport(startAt, endAt, id, format) {
        const params = {
            start_at: startAt,
            end_at: endAt,
            id,
        };

        return axios.post(`report/universal-report/download`, params, {
            headers: { Accept: format },
        });
    }
}
