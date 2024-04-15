import axios from 'axios';
import ReportService from '@/services/report.service';

export default class PlannedTimeReport extends ReportService {
    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param projects
     */
    getReport(projects) {
        return axios.post('report/planned-time', {
            projects,
        });
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param projects
     * @param format
     */
    downloadReport(projects, format) {
        return axios.post(
            `report/planned-time/download`,
            { projects },
            {
                headers: { Accept: format },
            },
        );
    }
}
