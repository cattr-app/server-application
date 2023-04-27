import { store } from '@/store';

/**
 * Gate Class
 */
class Gate {
    /**
     * @param {any} user
     */
    auth(user) {
        this.user = user;
    }

    /**
     * @param action
     * @param type
     * @param model
     * @returns {boolean|*}
     */
    allow(action, type, model = null) {
        if (!store.state['policies']['policies'][type]) {
            throw new Error(`Cannot find policy ${type}`);
        }

        return store.state['policies']['policies'][type][action](this.user, model);
    }

    /**
     * @param {*} action
     * @param {*} type
     * @param {*} model
     */
    deny(action, type, model = null) {
        return !this.allow(action, type, model);
    }
}

export default {
    install(Vue) {
        Vue.prototype._gate = new Gate();

        Object.defineProperty(Vue.prototype, '$gate', {
            get() {
                return Vue.prototype._gate;
            },
        });

        Object.defineProperty(Vue.prototype, '$can', {
            get() {
                return this.$gate.allow.bind(this.$gate);
            },
        });
    },
};
