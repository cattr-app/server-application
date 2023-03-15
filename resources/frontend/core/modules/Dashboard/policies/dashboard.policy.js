import { hasRole, hasProjectRole } from '@/utils/user';

export default class DashboardPolicy {
    static viewTeamTab(user) {
        return (
            hasRole(user, 'admin') ||
            hasRole(user, 'manager') ||
            hasRole(user, 'auditor') ||
            hasProjectRole(user, 'manager') ||
            hasProjectRole(user, 'auditor')
        );
    }

    static viewManualTime(user) {
        return hasRole(user, 'admin') || !!user.manual_time;
    }
}
