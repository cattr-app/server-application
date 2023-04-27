import { store } from '@/store';

/**
 * Determines whether the user has a role
 * @param {*} user
 * @param {*} roleName
 */
export function hasRole(user, roleName) {
    return user.role_id === store.getters['roles/roles'][roleName];
}

/**
 * Determines whether the user has a role in the project
 * @param {*} user
 * @param {*} roleName
 * @param projectId
 */
export function hasProjectRole(user, roleName, projectId = null) {
    if (!Object.prototype.hasOwnProperty.call(user, 'projects_relation')) {
        return false;
    }

    return user.projects_relation.some(projectRole => {
        if (projectId) {
            return (
                projectRole.role_id === store.getters['roles/roles'][roleName] && projectRole.project_id === projectId
            );
        }

        return projectRole.role_id === store.getters['roles/roles'][roleName];
    });
}
