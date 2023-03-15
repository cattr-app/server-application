import axios from 'axios';

export default class AboutService {
    async getGeneralInfo() {
        const result = await axios.get('about');

        return result.data;
    }

    async getStorageInfo() {
        const result = await axios.get('about/storage');

        return result.data.data;
    }

    startCleanup() {
        return axios.post('about/storage');
    }

    async getReportTypes() {
        const result = await axios.get('about/reports');

        return result.data.data.types;
    }
}
