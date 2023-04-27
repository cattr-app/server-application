import { store } from '@/store';
import DashboardPolicy from './dashboard.policy';

store.dispatch('policies/registerPolicies', {
    dashboard: DashboardPolicy,
});
