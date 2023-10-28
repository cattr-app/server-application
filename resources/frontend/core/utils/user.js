import { store } from '@/store';

/**
 * Determines whether the user has a role
 * @param {*} user
 * @param {*} roleName
 */
export function hasRole(user, roleName) {
    return user.role_id === store.getters['roles/roles'][roleName];
}
