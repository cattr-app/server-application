export class LocalStorage {
    private static instance: LocalStorage;

    private localStorage: Storage;

    static getStorage() {
        if (typeof LocalStorage.instance === "undefined") {
            LocalStorage.instance = new LocalStorage();
            LocalStorage.instance.localStorage = typeof window !== "undefined" ? window.localStorage : null;
        }

        return LocalStorage.instance;
    }

    constructor() {
        const instance = LocalStorage.instance;

        if (typeof instance !== "undefined" && instance !== this) {
            throw new Error("LocalStorage should be in single instance. Use `LocalStorage.getStorage()`");
        }
    }

    get(key: string) {
        let data = this.localStorage.getItem(key);

        return data === null ? null : JSON.parse(data);
    }

    set(key: string, value: any) {
        this.localStorage.setItem(key, JSON.stringify(value));
    }

    clear() {
        this.localStorage.clear();
    }
}
