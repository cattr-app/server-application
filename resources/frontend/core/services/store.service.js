export default class StoreService {
    storeNs = undefined;

    constructor(context) {
        this.context = context;
    }

    getStoreName(caller) {
        return `${this.storeNs}/${caller}`;
    }
}
