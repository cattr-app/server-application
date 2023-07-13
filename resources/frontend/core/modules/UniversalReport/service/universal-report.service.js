import ReportService from '@/services/report.service';
import axios from 'axios';

export default class UniversalReportService extends ReportService {
    constructor(taskService, projectService, userService) {
        super();
        this.taskService = taskService;
        this.projectService = projectService;
        this.userService = userService;
    }

    getMains() {
        return axios.get('report/universal-report/mains');
    }

    getDataObjectsAndFields(main) {
        return axios.get(`report/universal-report/data-objects-and-fields?main=${main}`);
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
}
