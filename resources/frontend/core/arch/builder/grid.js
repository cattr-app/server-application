import Builder from '../builder';
import set from 'lodash/set';

export default class Grid extends Builder {
    constructor(label, id, serviceClass, moduleContext, gridData = undefined, gridRouterPath = '') {
        super(moduleContext);

        this.label = label;
        this.id = id;
        this.routerConfig = {
            name: moduleContext.getModuleRouteName() + `.crud.${id}`,
            path: this.routerPrefix + '/' + gridRouterPath,
            component: () => import(/* webpackChunkName: "gridview" */ '../../views/Crud/GridView.vue'),
            meta: {
                auth: true,

                pageControls: [],
                gridData: {
                    title: label,
                    columns: [],
                    filters: [],
                    filterFields: [],
                    actions: [],

                    service: typeof serviceClass === 'object' ? serviceClass : new serviceClass(),

                    ...gridData,
                },
            },
        };
    }

    addColumn() {
        const arg = arguments[0];
        this.addToGridData('columns', arg);
        return this;
    }

    addAction() {
        const arg = arguments[0];
        this.addToGridData('actions', arg);
        return this;
    }

    addFilter() {
        const arg = arguments[0];
        this.addToGridData('filters', arg);
        return this;
    }

    addFilterField() {
        const arg = arguments[0];
        this.addToGridData('filterFields', arg);
        return this;
    }

    addToGridData(property, data) {
        if (Array.isArray(data)) {
            data.forEach(p => {
                this.routerConfig.meta.gridData[property].push(p);
            });
        } else {
            this.routerConfig.meta.gridData[property].push(data);
        }
    }

    addToMetaProperties(property, data, routerConfig) {
        set(routerConfig.meta, property, data);
    }

    addPageControls() {
        const data = arguments[0];
        if (Array.isArray(data)) {
            data.forEach(p => {
                this.routerConfig.meta.pageControls.push(p);
            });
        } else {
            this.routerConfig.meta.pageControls.push(data);
        }
        return this;
    }

    getRouterConfig() {
        return this.routerConfig;
    }
}
