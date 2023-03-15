import Builder from '../builder';
import View from './crud/view';
import Edit from './crud/edit';
import New from './crud/new';

export default class Crud extends Builder {
    view = {};
    edit = {};
    new = {};

    constructor(
        crudName,
        id,
        serviceClass,
        filters = {},
        moduleContext,
        defaultPrefix = '',
        hasPages = { edit: true, view: true, new: true },
    ) {
        super(moduleContext);

        this.moduleContext = moduleContext;
        this.crudName = crudName;
        this.id = id;
        this.serviceClass = typeof serviceClass === 'object' ? serviceClass : new serviceClass();
        this.hasEdit = hasPages.edit;
        this.hasView = hasPages.view;
        this.hasNew = hasPages.new;
        this.defaultPrefix = defaultPrefix;
        this.routerConfig = [];

        this.filters = filters;

        if (this.hasView) {
            this.view = new View(this);
        }

        if (this.hasEdit) {
            this.edit = new Edit(this);
        }

        if (this.hasNew) {
            this.new = new New(this);
        }
    }

    getRouterConfig() {
        const toReturn = [];
        if (this.view) {
            toReturn.push(this.view.getRouterConfig());
        }

        if (this.edit) {
            toReturn.push(this.edit.getRouterConfig());
        }

        if (this.new) {
            toReturn.push(this.new.getRouterConfig());
        }
        return toReturn;
    }
}
