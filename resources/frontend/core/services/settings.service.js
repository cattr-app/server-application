/**
 * Section service <abstract> class.
 * Used to fetch data from api for inside DynamicSettings.vue
 * Data is stored inside store -> settings -> sections -> data
 */
export default class SettingsService {
    /**
     * API endpoint URL
     * @returns string
     */
    getItemRequestUri() {
        throw new Error('getItemRequestUri must be implemented in SettingsService class');
    }

    /**
     * Fetch item data from api endpoint
     * @returns {data}
     */
    getAll() {
        throw new Error('getAll must be implemented in SettingsService class');
    }

    /**
     * Save item data
     * @param data
     * @returns {Promise<void>}
     */
    save(data) {
        throw new Error('save must be implemented in SettingsService class');
    }
}
