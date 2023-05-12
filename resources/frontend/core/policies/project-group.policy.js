import { hasRole } from '@/utils/user';

export default class ProjectGroupPolicy {
    static create(user) {
        return hasRole(user, 'admin') || hasRole(user, 'manager');
    }

    static update(user, model) {
        return hasRole(user, 'admin') || hasRole(user, 'manager');
    }

    static delete(user, model) {
        return hasRole(user, 'admin') || hasRole(user, 'manager');
    }
}
