export default class ReportService {
    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param startAt
     * @param endAt
     * @param users
     * @param projects
     */
    getReport(startAt, endAt, users, projects) {
        throw new Error('getReport must be implemented in ReportService class');
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param startAt
     * @param endAt
     * @param users
     * @param projects
     * @param format
     */
    downloadReport(startAt, endAt, users, projects, format) {
        throw new Error('downloadReport must be implemented in ReportService class');
    }
}
