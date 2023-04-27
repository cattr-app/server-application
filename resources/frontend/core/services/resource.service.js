export default class ResourceService {
    constructor(idParam = 'id') {
        this.idParam = idParam;
    }

    getItemRequestUri(id) {
        throw new Error('getItemRequestUri must be implemented in Resource class');
    }

    getIdParam() {
        return this.idParam;
    }

    getAll() {
        throw new Error('getAll must be implemented in Resource class');
    }

    getItem(id) {
        throw new Error('getItem must be implemented in Resource class');
    }

    deleteItem(id) {
        return undefined;
    }

    save(data, isNew = false) {
        return undefined;
    }

    create(data) {
        return undefined;
    }

    getOptionLabelKey() {
        return undefined;
    }

    getOptionList() {
        return this.getAll({ headers: { 'X-Paginate': 'false' } }).then(({ data }) =>
            data.data.map(option => ({
                value: option.id,
                label: option[this.getOptionLabelKey()],
            })),
        );
    }
}
