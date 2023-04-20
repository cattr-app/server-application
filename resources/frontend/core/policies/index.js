import { store } from '@/store';
import TaskPolicy from './task.policy';
import ProjectPolicy from './project.policy';
import UserPolicy from './user.policy';
import InvitationPolicy from './invitation.policy';
import PriorityPolicy from './priority.policy';
import StatusPolicy from './status.policy';

store.dispatch('policies/registerPolicies', {
    task: TaskPolicy,
    project: ProjectPolicy,
    user: UserPolicy,
    invitation: InvitationPolicy,
    priority: PriorityPolicy,
    status: StatusPolicy,
});
