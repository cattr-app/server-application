import axios from '@/config/app';

export default class OfflineSyncService {
    /**
     * API endpoint URL
     * @returns string
     */
    getItemRequestUri() {
        return `offline-sync`;
    }

    /**
     * Upload file
     * @returns {Promise<void>}
     * @param payload
     */
    async upload(payload) {
        const formData = new FormData();
        formData.append('file', payload);

        const { data } = await axios.post(this.getItemRequestUri() + '/upload-intervals', formData);
        return data;
    }
}
