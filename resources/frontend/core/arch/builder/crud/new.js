import AbstractCrud from './abstractCrud';

export default class New extends AbstractCrud {
    context = {};
    routerConfig = {};

    constructor(context) {
        super();
        this.context = context;

        this.routerConfig = {
            path: `${context.routerPrefix}${context.defaultPrefix.length ? '/' + context.defaultPrefix : ''}/new`,
            name: this.getNewRouteName(),
            component: () => import(/* webpackChunkName: "editview" */ '@/views/Crud/EditView.vue'),
            meta: {
                auth: true,
                service: context.serviceClass,
                filters: context.filters,

                fields: [],
                pageData: {
                    title: context.crudName,
                    type: 'new',
                    pageControls: [],
                },
            },
        };
    }

    /**
     *
     * @param config
     * @returns {New}
     */
    addPageControls(config) {
        this.addToMetaProperties('pageData.pageControls', config, this.getRouterConfig());
        return this;
    }

    /**
     * @param fieldConfig
     * @returns {New}
     */
    addField(fieldConfig) {
        this.addToMetaProperties('fields', fieldConfig, this.getRouterConfig());
        return this;
    }

    getRouterConfig() {
        return this.routerConfig;
    }

    getNewRouteName() {
        return this.context.moduleContext.getModuleRouteName() + `.crud.${this.context.id}.new`;
    }
}
