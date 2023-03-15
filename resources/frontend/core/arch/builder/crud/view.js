import AbstractCrud from './abstractCrud';

export default class View extends AbstractCrud {
    context = {};
    routerConfig = {};

    constructor(context) {
        super();
        this.context = context;

        this.routerConfig = {
            path: `${context.routerPrefix}${context.defaultPrefix.length ? '/' + context.defaultPrefix : ''}/view/:id`,
            name: this.getViewRouteName(context),
            component: () => import(/* webpackChunkName: "itemview" */ '@/views/Crud/ItemView.vue'),
            meta: {
                auth: true,
                service: context.serviceClass,
                filters: context.filters,

                fields: [],
                pageData: {
                    title: context.crudName,
                    type: 'view',
                    pageControls: [],
                },
            },
        };
    }

    addPageControls() {
        const arg = arguments[0];
        this.addToMetaProperties('pageData.pageControls', arg, this.getRouterConfig());
        return this;
    }

    /**
     * Add field to page
     * @param {Object} fieldConfig
     * @returns {View}
     */
    addField(fieldConfig) {
        this.addToMetaProperties('fields', fieldConfig, this.getRouterConfig());
        return this;
    }

    getRouterConfig() {
        return this.routerConfig;
    }

    getViewRouteName() {
        return this.context.moduleContext.getModuleRouteName() + `.crud.${this.context.id}.view`;
    }
}
