import { hasRole } from '@/utils/user';

export default class ProjectPolicy {
    static create(user) {
        return hasRole(user, 'admin') || hasRole(user, 'manager');
    }

    static update(user, model) {
        if (!model) {
            return false;
        }

        return model.can.update;
    }

    static updateMembers(user, model) {
        if (!model) {
            return false;
        }

        return model.can.update_members;
    }

    static delete(user, model) {
        if (!model) {
            return false;
        }

        return model.can.destroy;
    }
}
