import Store from '@/store';
import DashboardPolicy from './dashboard.policy';

Store.dispatch('policies/registerPolicies', {
    dashboard: DashboardPolicy,
});
