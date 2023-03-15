import { hasRole, hasProjectRole } from '@/utils/user';

export default class TaskPolicy {
    static create(user) {
        return hasRole(user, 'admin') || hasRole(user, 'manager') || hasProjectRole(user, 'manager');
    }

    static update(user, model) {
        if (!model) {
            return false;
        }

        return model.can.update;
    }

    static delete(user, model) {
        if (!model) {
            return false;
        }

        return model.can.destroy;
    }
}
