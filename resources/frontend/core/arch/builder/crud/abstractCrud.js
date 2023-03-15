import set from 'lodash/set';

export default class AbstractCrud {
    /**
     * @param property
     * @param data
     * @param routerConfig
     */
    addToMetaProperties(property, data, routerConfig) {
        set(routerConfig.meta, property, data);
    }
}
