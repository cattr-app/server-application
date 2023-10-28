export default class TaskPolicy {
    static create(user) {
        return user.can_create_task;
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
