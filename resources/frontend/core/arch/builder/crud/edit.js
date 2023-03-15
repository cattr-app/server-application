import AbstractCrud from './abstractCrud';

export default class Edit extends AbstractCrud {
    context = {};
    routerConfig = {};

    constructor(context) {
        super();
        this.context = context;

        this.routerConfig = {
            path: `${context.routerPrefix}${context.defaultPrefix.length ? '/' + context.defaultPrefix : ''}/edit/:id`,
            name: this.getEditRouteName(),
            component: () => import(/* webpackChunkName: "editview" */ '@/views/Crud/EditView.vue'),
            meta: {
                auth: true,
                service: context.serviceClass,
                filters: context.filters,

                fields: [],
                pageData: {
                    title: context.crudName,
                    type: 'edit',
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
     * @param {Config} fieldConfig
     * @returns {Edit}
     */
    addField(fieldConfig) {
        this.addToMetaProperties('fields', fieldConfig, this.getRouterConfig());
        return this;
    }

    getRouterConfig() {
        return this.routerConfig;
    }

    getEditRouteName() {
        return this.context.moduleContext.getModuleRouteName() + `.crud.${this.context.id}.edit`;
    }
}
