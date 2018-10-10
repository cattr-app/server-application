import {LocalStorage} from '../api/storage.model';

const localStorage = LocalStorage.getStorage();

export function loadAdminStorage() {
  localStorage.set('attached_users', [
    {
      'id': 2,
      'full_name': 'Attached user',
      'first_name': 'Attached',
      'last_name': 'User',
      'email': 'www@wefef.eru',
      'url': null,
      'company_id': null,
      'level': null,
      'payroll_access': null,
      'billing_access': null,
      'avatar': 'q',
      'screenshots_active': 1,
      'manual_time': 0,
      'permanent_tasks': null,
      'computer_time_popup': null,
      'poor_time_popup': null,
      'blur_screenshots': null,
      'web_and_app_monitoring': null,
      'webcam_shots': null,
      'screenshots_interval': 300,
      'user_role_value': null,
      'active': '1',
      'deleted_at': null,
      'created_at': '2018-09-03 13:12:49',
      'updated_at': '2018-09-03 13:12:49',
      'role_id': 2,
      'timezone': 'Asia/Almaty'
    }
  ]);
  localStorage.set('attached_projects', [
    {
      'id': 1,
      'company_id': 0,
      'name': 'Similique harum voluptas ut corporis.',
      'description': 'Quo vi.',
      'deleted_at': null,
      'created_at': '2018-09-03 02:10:51',
      'updated_at': '2018-09-03 02:10:51'
    },
    {
      'id': 2,
      'company_id': 1,
      'name': 'Omnis nihil rerum vel eum quam.',
      'description': 'Ducimus voluptas assumenda facere quis. Repellendus commodi nobis ut ullam est.',
      'deleted_at': null,
      'created_at': '2018-09-03 02:10:57',
      'updated_at': '2018-09-03 02:10:57'
    },
    {
      'id': 3,
      'company_id': 2,
      'name': 'Quidem fuga.',
      'description': 'Voluptas quod nesciunt velit ipsam enim autem reprehenderit.',
      'deleted_at': null,
      'created_at': '2018-09-03 02:14:21',
      'updated_at': '2018-09-03 02:14:21'
    },
    {
      'id': 4,
      'company_id': 3,
      'name': 'Magnam quis saepe aut quae quas.',
      'description': 'Illo a numquam molestiae explicabo.',
      'deleted_at': null,
      'created_at': '2018-09-03 02:18:25',
      'updated_at': '2018-09-03 02:18:25'
    },
    {
      'id': 5,
      'company_id': 4,
      'name': 'Laboriosam est rerum sit.',
      'description': 'Voluptatem consequatur fugit pariatur porro voluptatem eum.',
      'deleted_at': null,
      'created_at': '2018-09-03 02:22:26',
      'updated_at': '2018-09-03 02:22:26'
    },
    {
      'id': 6,
      'company_id': null,
      'name': 'dfkhk',
      'description': 'kjnfdvk',
      'deleted_at': null,
      'created_at': '2018-09-24 07:03:10',
      'updated_at': '2018-09-24 07:03:10'
    }
  ]);
  localStorage.set('allowed_actions', [
    {
      'object': 'attached-users',
      'action': 'bulk-create',
      'name': 'Attached User relation multiple create'
    },
    {
      'object': 'attached-users',
      'action': 'bulk-remove',
      'name': 'Attached User relation multiple remove'
    },
    {
      'object': 'attached-users',
      'action': 'create',
      'name': 'Attached User relation create'
    },
    {
      'object': 'attached-users',
      'action': 'full_access',
      'name': 'Attached User relation full access'
    },
    {
      'object': 'attached-users',
      'action': 'list',
      'name': 'Attached User relation list'
    },
    {
      'object': 'attached-users',
      'action': 'remove',
      'name': 'Attached User relation remove'
    },
    {
      'object': 'dashboard',
      'action': 'manager_access',
      'name': 'Dashboard manager access'
    },
    {
      'object': 'project-report',
      'action': 'list',
      'name': 'Projects report list'
    },
    {
      'object': 'project-report',
      'action': 'manager_access',
      'name': 'Projects report manager access'
    },
    {
      'object': 'project-report',
      'action': 'projects',
      'name': 'Projects report related projects'
    },
    {
      'object': 'projects',
      'action': 'create',
      'name': 'Project create'
    },
    {
      'object': 'projects',
      'action': 'edit',
      'name': 'Project edit'
    },
    {
      'object': 'projects',
      'action': 'full_access',
      'name': 'Project full access'
    },
    {
      'object': 'projects',
      'action': 'list',
      'name': 'Project list'
    },
    {
      'object': 'projects',
      'action': 'relations',
      'name': 'Project list attached to user'
    },
    {
      'object': 'projects',
      'action': 'remove',
      'name': 'Project remove'
    },
    {
      'object': 'projects',
      'action': 'show',
      'name': 'Project show'
    },
    {
      'object': 'projects-roles',
      'action': 'bulk-create',
      'name': 'Project Role relation multiple create'
    },
    {
      'object': 'projects-roles',
      'action': 'bulk-remove',
      'name': 'Project Role relation multiple remove'
    },
    {
      'object': 'projects-roles',
      'action': 'create',
      'name': 'Project Role relation create'
    },
    {
      'object': 'projects-roles',
      'action': 'full_access',
      'name': 'Project Role relation full access'
    },
    {
      'object': 'projects-roles',
      'action': 'list',
      'name': 'Project Role relation list'
    },
    {
      'object': 'projects-roles',
      'action': 'remove',
      'name': 'Project Role relation remove'
    },
    {
      'object': 'projects-users',
      'action': 'bulk-create',
      'name': 'Project User relation multiple create'
    },
    {
      'object': 'projects-users',
      'action': 'bulk-remove',
      'name': 'Project User relation multiple remove'
    },
    {
      'object': 'projects-users',
      'action': 'create',
      'name': 'Project User relation create'
    },
    {
      'object': 'projects-users',
      'action': 'full_access',
      'name': 'Project User relation full access'
    },
    {
      'object': 'projects-users',
      'action': 'list',
      'name': 'Project User relation list'
    },
    {
      'object': 'projects-users',
      'action': 'remove',
      'name': 'Project User relation remove'
    },
    {
      'object': 'roles',
      'action': 'allowed-rules',
      'name': 'Role allowed rule list'
    },
    {
      'object': 'roles',
      'action': 'create',
      'name': 'Role create'
    },
    {
      'object': 'roles',
      'action': 'edit',
      'name': 'Role edit'
    },
    {
      'object': 'roles',
      'action': 'full_access',
      'name': 'Roles full access'
    },
    {
      'object': 'roles',
      'action': 'list',
      'name': 'Role list'
    },
    {
      'object': 'roles',
      'action': 'remove',
      'name': 'Role remove'
    },
    {
      'object': 'roles',
      'action': 'show',
      'name': 'Role show'
    },
    {
      'object': 'rules',
      'action': 'actions',
      'name': 'Rules actions list'
    },
    {
      'object': 'rules',
      'action': 'bulk-edit',
      'name': 'Rules multiple edit'
    },
    {
      'object': 'rules',
      'action': 'edit',
      'name': 'Rules edit'
    },
    {
      'object': 'screenshots',
      'action': 'create',
      'name': 'Screenshot create'
    },
    {
      'object': 'screenshots',
      'action': 'dashboard',
      'name': 'Screenshot list at dashboard'
    },
    {
      'object': 'screenshots',
      'action': 'edit',
      'name': 'Screenshot edit'
    },
    {
      'object': 'screenshots',
      'action': 'full_access',
      'name': 'Screenshots full access'
    },
    {
      'object': 'screenshots',
      'action': 'list',
      'name': 'Screenshot list'
    },
    {
      'object': 'screenshots',
      'action': 'manager_access',
      'name': 'Screenshots manager access'
    },
    {
      'object': 'screenshots',
      'action': 'remove',
      'name': 'Screenshot remove'
    },
    {
      'object': 'screenshots',
      'action': 'show',
      'name': 'Screenshot show'
    },
    {
      'object': 'tasks',
      'action': 'create',
      'name': 'Task create'
    },
    {
      'object': 'tasks',
      'action': 'dashboard',
      'name': 'Task list at dashboard'
    },
    {
      'object': 'tasks',
      'action': 'edit',
      'name': 'Task edit'
    },
    {
      'object': 'tasks',
      'action': 'full_access',
      'name': 'Tasks full access'
    },
    {
      'object': 'tasks',
      'action': 'list',
      'name': 'Task list'
    },
    {
      'object': 'tasks',
      'action': 'remove',
      'name': 'Task remove'
    },
    {
      'object': 'tasks',
      'action': 'show',
      'name': 'Task show'
    },
    {
      'object': 'time',
      'action': 'full_access',
      'name': 'Time full access'
    },
    {
      'object': 'time',
      'action': 'project',
      'name': 'Time by project'
    },
    {
      'object': 'time',
      'action': 'task',
      'name': 'Time by single task'
    },
    {
      'object': 'time',
      'action': 'task-user',
      'name': 'Time by single task and user'
    },
    {
      'object': 'time',
      'action': 'tasks',
      'name': 'Time by tasks'
    },
    {
      'object': 'time',
      'action': 'total',
      'name': 'Time total'
    },
    {
      'object': 'time-intervals',
      'action': 'create',
      'name': 'Time interval create'
    },
    {
      'object': 'time-intervals',
      'action': 'edit',
      'name': 'Time interval edit'
    },
    {
      'object': 'time-intervals',
      'action': 'full_access',
      'name': 'Time intervals full access'
    },
    {
      'object': 'time-intervals',
      'action': 'list',
      'name': 'Time interval list'
    },
    {
      'object': 'time-intervals',
      'action': 'remove',
      'name': 'Time interval remove'
    },
    {
      'object': 'time-intervals',
      'action': 'show',
      'name': 'Time interval show'
    },
    {
      'object': 'users',
      'action': 'bulk-edit',
      'name': 'User multiple edit'
    },
    {
      'object': 'users',
      'action': 'create',
      'name': 'User create'
    },
    {
      'object': 'users',
      'action': 'edit',
      'name': 'User edit'
    },
    {
      'object': 'users',
      'action': 'full_access',
      'name': 'Users full access'
    },
    {
      'object': 'users',
      'action': 'list',
      'name': 'User list'
    },
    {
      'object': 'users',
      'action': 'relations',
      'name': 'Attached users list'
    },
    {
      'object': 'users',
      'action': 'remove',
      'name': 'User remove'
    },
    {
      'object': 'users',
      'action': 'show',
      'name': 'User show'
    }
  ]);
  localStorage.set('user',
    {
      'id': 1,
      'full_name': 'Admin',
      'first_name': 'Ad',
      'last_name': 'Min',
      'email': 'admin@example.com',
      'level': 'admin',
      'user_role_value': '1',
      'active': 'active',
      'deleted_at': null,
      'role_id': 1,
      'timezone': null
    }
  );
  localStorage.set('token', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTUzOTAwNjAwNCwiZXhwIjoxNTM5MDA5NjA0LCJuYmYiOjE1MzkwMDYwMDQsImp0aSI6IjZ0TFpXRE02ZlFwYmlCdk8iLCJzdWIiOjEsInBydiI6Ijg3ZTBhZjFlZjlmZDE1ODEyZmRlYzk3MTUzYTE0ZTBiMDQ3NTQ2YWEifQ.kuhSY8Ae7k5vSHHXelN0gxEcJpYO6268Sv-XA1H47ZI');
  localStorage.set('tokenType', 'bearer');
}

export function loadUserStorage() {
  localStorage.set('allowed_actions', [
    {
      'object': 'project-report',
      'action': 'list',
      'name': 'Projects report list'
    },
    {
      'object': 'project-report',
      'action': 'projects',
      'name': 'Projects report related projects'
    },
    {
      'object': 'projects',
      'action': 'list',
      'name': 'Project list'
    },
    {
      'object': 'projects',
      'action': 'show',
      'name': 'Project show'
    },
    {
      'object': 'roles',
      'action': 'allowed-rules',
      'name': 'Role allowed rule list'
    },
    {
      'object': 'screenshots',
      'action': 'create',
      'name': 'Screenshot create'
    },
    {
      'object': 'screenshots',
      'action': 'dashboard',
      'name': 'Screenshot list at dashboard'
    },
    {
      'object': 'screenshots',
      'action': 'list',
      'name': 'Screenshot list'
    },
    {
      'object': 'screenshots',
      'action': 'remove',
      'name': 'Screenshot remove'
    },
    {
      'object': 'screenshots',
      'action': 'show',
      'name': 'Screenshot show'
    },
    {
      'object': 'tasks',
      'action': 'create',
      'name': 'Task create'
    },
    {
      'object': 'tasks',
      'action': 'dashboard',
      'name': 'Task list at dashboard'
    },
    {
      'object': 'tasks',
      'action': 'edit',
      'name': 'Task edit'
    },
    {
      'object': 'tasks',
      'action': 'list',
      'name': 'Task list'
    },
    {
      'object': 'tasks',
      'action': 'remove',
      'name': 'Task remove'
    },
    {
      'object': 'tasks',
      'action': 'show',
      'name': 'Task show'
    },
    {
      'object': 'time',
      'action': 'project',
      'name': 'Time by project'
    },
    {
      'object': 'time',
      'action': 'task',
      'name': 'Time by single task'
    },
    {
      'object': 'time',
      'action': 'task-user',
      'name': 'Time by single task and user'
    },
    {
      'object': 'time',
      'action': 'tasks',
      'name': 'Time by tasks'
    },
    {
      'object': 'time',
      'action': 'total',
      'name': 'Time total'
    },
    {
      'object': 'time-intervals',
      'action': 'create',
      'name': 'Time interval create'
    },
    {
      'object': 'time-intervals',
      'action': 'list',
      'name': 'Time interval list'
    },
    {
      'object': 'time-intervals',
      'action': 'remove',
      'name': 'Time interval remove'
    },
    {
      'object': 'time-intervals',
      'action': 'show',
      'name': 'Time interval show'
    },
    {
      'object': 'users',
      'action': 'edit',
      'name': 'User edit'
    },
    {
      'object': 'users',
      'action': 'list',
      'name': 'User list'
    },
    {
      'object': 'users',
      'action': 'show',
      'name': 'User show'
    }
  ]);
  localStorage.set('attached_projects', [
    {
      'id': 1,
      'company_id': 0,
      'name': 'Similique harum voluptas ut corporis.',
      'description': 'Quo vi.',
      'deleted_at': null,
      'created_at': '2018-09-03 02:10:51',
      'updated_at': '2018-09-03 02:10:51'
    },
    {
      'id': 2,
      'company_id': 1,
      'name': 'Omnis nihil rerum vel eum quam.',
      'description': 'Ducimus voluptas assumenda facere quis. Repellendus commodi nobis ut ullam est.',
      'deleted_at': null,
      'created_at': '2018-09-03 02:10:57',
      'updated_at': '2018-09-03 02:10:57'
    },
    {
      'id': 3,
      'company_id': 2,
      'name': 'Quidem fuga.',
      'description': 'Voluptas quod nesciunt velit ipsam enim autem reprehenderit.',
      'deleted_at': null,
      'created_at': '2018-09-03 02:14:21',
      'updated_at': '2018-09-03 02:14:21'
    },
    {
      'id': 4,
      'company_id': 3,
      'name': 'Magnam quis saepe aut quae quas.',
      'description': 'Illo a numquam molestiae explicabo.',
      'deleted_at': null,
      'created_at': '2018-09-03 02:18:25',
      'updated_at': '2018-09-03 02:18:25'
    },
    {
      'id': 5,
      'company_id': 4,
      'name': 'Laboriosam est rerum sit.',
      'description': 'Voluptatem consequatur fugit pariatur porro voluptatem eum.',
      'deleted_at': null,
      'created_at': '2018-09-03 02:22:26',
      'updated_at': '2018-09-03 02:22:26'
    },
    {
      'id': 6,
      'company_id': null,
      'name': 'dfkhk',
      'description': 'kjnfdvk',
      'deleted_at': null,
      'created_at': '2018-09-24 07:03:10',
      'updated_at': '2018-09-24 07:03:10'
    }
  ]);
  localStorage.set('user',
    {
      'id': 2,
      'full_name': 'User',
      'first_name': 'user',
      'last_name': 'user',
      'email': 'www@wefef.eru',
      'active': '1',
      'role_id': 2,
    }
  );
  localStorage.set('token', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTUzOTAwNjAwNCwiZXhwIjoxNTM5MDA5NjA0LCJuYmYiOjE1MzkwMDYwMDQsImp0aSI6IjZ0TFpXRE02ZlFwYmlCdk8iLCJzdWIiOjEsInBydiI6Ijg3ZTBhZjFlZjlmZDE1ODEyZmRlYzk3MTUzYTE0ZTBiMDQ3NTQ2YWEifQ.kuhSY8Ae7k5vSHHXelN0gxEcJpYO6268Sv-XA1H47ZI');
  localStorage.set('tokenType', 'bearer');
}
