/**
 * Determines whether the user has a role
 * @param {*} user
 * @param {*} roleName
 */
export function hasRole(user, roleName) {
    if (roleName === 'admin') {
        return !!user.is_admin;
    }

    return user.role.name === roleName;
}

/**
 * Determines whether the user has a role in the project
 * @param {*} user
 * @param {*} roleName
 */
export function hasProjectRole(user, roleName, projectId = null) {
    if (!user.hasOwnProperty('projects_relation')) {
        return false;
    }

    return user.projects_relation.some(projectRole => {
        if (projectId) {
            return projectRole.role.name === roleName && projectRole.project_id === projectId;
        }

        return projectRole.role.name === roleName;
    });
}
