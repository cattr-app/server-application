import axios from '@/config/app';
import SettingsService from '@/services/settings.service';

/**
 * Section service class.
 * Used to fetch data from api for inside DynamicSettings.vue
 * Data is stored inside store -> settings -> sections -> data
 */
export default class CompanyService extends SettingsService {
    /**
     * API endpoint URL
     * @returns string
     */
    getItemRequestUri() {
        return `company-settings`;
    }

    /**
     * Fetch item data from api endpoint
     * @returns {data}
     */
    async getAll() {
        return (await axios.get(this.getItemRequestUri(), { ignoreCancel: true })).data.data;
    }

    /**
     * Save item data
     * @param data
     * @returns {Promise<void>}
     */
    async save(payload) {
        const { data } = await axios.patch(this.getItemRequestUri(), payload);
        return data;
    }
}
