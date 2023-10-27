import { hasRole } from '@/utils/user';

export default class DashboardPolicy {
    static viewTeamTab(user) {
        return user.can_view_team_tab;
    }

    static viewManualTime(user) {
        return hasRole(user, 'admin') || !!user.manual_time;
    }
}
