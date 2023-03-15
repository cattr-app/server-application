export default class Builder {
    routerConfig = {};

    constructor(moduleContext) {
        this.moduleContext = moduleContext;
        this.routerPrefix =
            (moduleContext.getRouterPrefix().startsWith('/') ? '' : '/') + moduleContext.getRouterPrefix();
    }
}
