import { hasRole } from '@/utils/user';

export default class StatusPolicy {
    static viewAny(user) {
        return hasRole(user, 'admin');
    }

    static create(user) {
        return hasRole(user, 'admin');
    }

    static update(user, model) {
        return hasRole(user, 'admin');
    }

    static delete(user, model) {
        return hasRole(user, 'admin');
    }
}
