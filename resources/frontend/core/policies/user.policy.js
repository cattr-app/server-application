import { hasRole } from '@/utils/user';

export default class UserPolicy {
    static viewAny(user) {
        return hasRole(user, 'admin');
    }

    static create(user) {
        return hasRole(user, 'admin');
    }

    static update(user) {
        return hasRole(user, 'admin');
    }
}
