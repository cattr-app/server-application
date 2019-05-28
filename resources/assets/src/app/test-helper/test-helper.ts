import {LocalStorage} from '../api/storage.model';

const localStorage = LocalStorage.getStorage();

export function loadAdminStorage() {
  localStorage.clear();
  localStorage.set('attached_users', [
    {
      'id': 2,
      'full_name': 'Attached user',
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
  localStorage.set('settings-tab', 'Account');
}

export function loadUserStorage() {
  localStorage.clear();
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
      'email': 'www@wefef.eru',
      'active': '1',
      'role_id': 2,
    }
  );
  localStorage.set('token', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTUzOTAwNjAwNCwiZXhwIjoxNTM5MDA5NjA0LCJuYmYiOjE1MzkwMDYwMDQsImp0aSI6IjZ0TFpXRE02ZlFwYmlCdk8iLCJzdWIiOjEsInBydiI6Ijg3ZTBhZjFlZjlmZDE1ODEyZmRlYzk3MTUzYTE0ZTBiMDQ3NTQ2YWEifQ.kuhSY8Ae7k5vSHHXelN0gxEcJpYO6268Sv-XA1H47ZI');
  localStorage.set('tokenType', 'bearer');
  localStorage.set('settings-tab', 'Account');
}

export function loadManagerStorage() {
    localStorage.clear();
    localStorage.set('attached_users', [
        {
          "id":13,
          "full_name":"User 1",
          "email":"user1@example.com",
          "url":null,
          "company_id":1,
          "level":null,
          "payroll_access":1,
          "billing_access":1,
          "avatar":null,
          "screenshots_active":1,
          "manual_time":0,
          "permanent_tasks":0,
          "computer_time_popup":300,
          "poor_time_popup":null,
          "blur_screenshots":0,
          "web_and_app_monitoring":1,
          "webcam_shots":0,
          "screenshots_interval":9,
          "user_role_value":null,
          "active":1,
          "deleted_at":null,
          "created_at":"2018-08-07 05:45:11",
          "updated_at":"2018-10-18 00:26:17",
          "role_id":2,
          "timezone":"Asia/Novosibirsk",
          "pivot":{
            "project_id":6,
            "user_id":13
          }
        },

        {"id":22,
        "full_name":"manager2",
        "email":"manager2@example.com",
        "url":null,
        "company_id":null,
        "level":null,
        "payroll_access":null,
        "billing_access":null,
        "avatar":null,
        "screenshots_active":1,
        "manual_time":0,
        "permanent_tasks":null,
        "computer_time_popup":5,
        "poor_time_popup":null,
        "blur_screenshots":null,
        "web_and_app_monitoring":null,
        "webcam_shots":null,
        "screenshots_interval":5,
        "user_role_value":null,
        "active":1,
        "deleted_at":null,
        "created_at":"2018-10-24 10:13:12",
        "updated_at":"2018-10-24 10:13:12",
        "role_id":5,
        "timezone":"Asia/Omsk",
        "pivot":{
          "project_id":6,
          "user_id":22
        }
      },

      {
        "id":1,
        "full_name":"Ad Min",
        "email":"admin@example.com",
        "url":null,
        "company_id":1,
        "level":"admin",
        "payroll_access":1,
        "billing_access":1,
        "avatar":null,
        "screenshots_active":1,
        "manual_time":0,
        "permanent_tasks":0,
        "computer_time_popup":300,
        "poor_time_popup":null,
        "blur_screenshots":0,
        "web_and_app_monitoring":1,
        "webcam_shots":0,
        "screenshots_interval":9,
        "user_role_value":null,
        "active":1,
        "deleted_at":null,
        "created_at":"2018-08-07 04:51:08",
        "updated_at":"2018-08-31 10:38:15",
        "role_id":1,"timezone":"Asia/Omsk"
      },

      {
        "id":13,
        "full_name":"User 1",
        "email":"user1@example.com",
        "url":null,
        "company_id":1,
        "level":null,
        "payroll_access":1,
        "billing_access":1,
        "avatar":null,
        "screenshots_active":1,
        "manual_time":0,
        "permanent_tasks":0,
        "computer_time_popup":300,
        "poor_time_popup":null,
        "blur_screenshots":0,
        "web_and_app_monitoring":1,
        "webcam_shots":0,
        "screenshots_interval":9,
        "user_role_value":null,
        "active":1,
        "deleted_at":null,
        "created_at":"2018-08-07 05:45:11",
        "updated_at":"2018-10-18 00:26:17",
        "role_id":2,
        "timezone":"Asia/Novosibirsk"
      },

      {
        "id":14,
        "full_name":"User 2",
        "email":"user2@example.com",
        "url":null,"company_id":1,
        "level":null,"payroll_access":1,
        "billing_access":1,
        "avatar":null,
        "screenshots_active":1,
        "manual_time":0,
        "permanent_tasks":0,
        "computer_time_popup":300,
        "poor_time_popup":null,
        "blur_screenshots":0,
        "web_and_app_monitoring":1,
        "webcam_shots":0,
        "screenshots_interval":9,
        "user_role_value":null,
        "active":1,
        "deleted_at":null,
        "created_at":"2018-08-07 05:45:13",
        "updated_at":"2018-08-31 06:38:32",
        "role_id":2,
        "timezone":"Asia/Novosibirsk"
      },

      {
        "id":19,
        "full_name":"Alexander Yanchuk",
        "email":"alexanderyanchuk95@gmail.com",
        "url":null,
        "company_id":null,
        "level":"2",
        "payroll_access":1,
        "billing_access":null,
        "avatar":null,
        "screenshots_active":1,
        "manual_time":0,
        "permanent_tasks":0,
        "computer_time_popup":300,
        "poor_time_popup":null,
        "blur_screenshots":0,
        "web_and_app_monitoring":1,
        "webcam_shots":0,
        "screenshots_interval":5,
        "user_role_value":null,
        "active":1,
        "deleted_at":null,
        "created_at":"2018-08-28 05:01:55",
        "updated_at":"2018-10-22 02:17:44",
        "role_id":5,
        "timezone":"Asia/Omsk"
      }
    ]);
    localStorage.set('attached_projects', [
      {
        "id":1,
        "company_id":0,
        "name":"Qui nihil.",
        "description":"Laboriosam excepturi harum et sapiente. Veritatis incidunt neque aut dolor nesciunt. Expedita est totam voluptate cumque. Quia occaecati non blanditiis nihil non. Maxime nihil atque deleniti nemo. Necessitatibus vel minus accusantium aperiam. Aliquid qui neque vel facilis sint. Sunt laudantium assumenda quaerat accusantium laborum ipsa. Repellat voluptatem repellat sed voluptatem. Aspernatur voluptate nulla soluta qui officia sit sint maxime. Voluptatem aut vel ut est voluptas omnis. Et natus quae et fuga quo. Sapiente non non voluptatem alias in aliquid. Officia non animi est tempore reprehenderit ea. Amet rerum facere illum et quae. Quia iste et ad voluptas.",
        "deleted_at":null,
        "created_at":"2018-08-07 04:51:08",
        "updated_at":"2018-08-07 04:51:08"
      },

      {
        "id":6,
        "company_id":4,
        "name":"Amazing Time",
        "description":"",
        "deleted_at":null,
        "created_at":"2018-08-17 07:08:40",
        "updated_at":"2018-08-17 07:08:40"
      }
    ]);
    localStorage.set('allowed_actions', [
      {
        "object":"attached-users",
        "action":"list",
        "name":"Attached User relation list"
      },

      {
        "object":"dashboard",
        "action":"manager_access",
        "name":"Dashboard manager access"
      },

      {
        "object":"project-report",
        "action":"list",
        "name":"Projects report list"
      },

      {
        "object":"project-report",
        "action":"manager_access",
        "name":"Projects report manager access"
      },

      {
        "object":"project-report",
        "action":"projects",
        "name":"Projects report related projects"
      },

      {
        "object":"projects",
        "action":"list",
        "name":"Project list"
      },

      {
        "object":"projects",
        "action":"relations",
        "name":"Project list attached to user"
      },

      {
        "object":"projects",
        "action":"show",
        "name":"Project show"
      },

      {
        "object":"projects-roles",
        "action":"list",
        "name":"Project Role relation list"
      },

      {
        "object":"projects-users",
        "action":"list",
        "name":"Project User relation list"
      },

      {
        "object":"roles",
        "action":"allowed-rules",
        "name":"Role allowed rule list"
      },

      {
        "object":"roles",
        "action":"list",
        "name":"Role list"
      },

      {
        "object":"screenshots",
        "action":"dashboard",
        "name":"Screenshot list at dashboard"
      },

      {
        "object":"screenshots",
        "action":"edit",
        "name":"Screenshot edit"
      },

      {
        "object":"screenshots",
        "action":"list",
        "name":"Screenshot list"
      },

      {
        "object":"screenshots",
        "action":"manager_access",
        "name":"Screenshots manager access"
      },

      {
        "object":"screenshots",
        "action":"remove",
        "name":"Screenshot remove"
      },

      {
        "object":"screenshots",
        "action":"show",
        "name":"Screenshot show"
      },

      {
        "object":"tasks",
        "action":"dashboard",
        "name":"Task list at dashboard"
      },

      {
        "object":"tasks",
        "action":"list",
        "name":"Task list"
      },

      {
        "object":"tasks",
        "action":"show",
        "name":"Task show"
      },

      {
        "object":"time",
        "action":"project",
        "name":"Time by project"
      },

      {
        "object":"time",
        "action":"task",
        "name":"Time by single task"
      },

      {
        "object":"time",
        "action":"task-user",
        "name":"Time by single task and user"
      },

      {
        "object":"time",
        "action":"tasks",
        "name":"Time by tasks"
      },

      {
        "object":"time-duration",
        "action":"list",
        "name":"Time duration list"
      },

      {
        "object":"time-intervals",
        "action":"list",
        "name":"Time interval list"
      },

      {
        "object":"time-intervals",
        "action":"manager_access",
        "name":"Time intervals manager access"
      },

      {
        "object":"time-intervals",
        "action":"show",
        "name":"Time interval show"
      },

      {
        "object":"time-use-report",
        "action":"list",
        "name":"Time use report list"
      },

      {
        "object":"users",
        "action":"list",
        "name":"User list"
      },

      {
        "object":"users",
        "action":"relations",
        "name":"Attached users list"
      },

      {
        "object":"users",
        "action":"show",
        "name":"User show"
      }
    ]);
    localStorage.set('user',
    {
      "id":12,
      "full_name":"Manager",
      "email":"manager@example.com",
      "url":null,
      "company_id":1,
      "level":null,
      "payroll_access":1,
      "billing_access":1,
      "avatar":null,
      "screenshots_active":1,
      "manual_time":0,
      "permanent_tasks":0,
      "computer_time_popup":300,
      "poor_time_popup":null,
      "blur_screenshots":0,
      "web_and_app_monitoring":1,
      "webcam_shots":0,
      "screenshots_interval":9,
      "user_role_value":null,
      "active":1,
      "deleted_at":null,
      "created_at":"2018-08-07 05:45:10",
      "updated_at":"2018-10-18 00:25:49",
      "role_id":5,
      "timezone":"Asia/Novosibirsk"
      }
    );
    localStorage.set('token', "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9hbWF6aW5nLXRpbWUubG9jOjgwMDBcL2FwaVwvYXV0aFwvbG9naW4iLCJpYXQiOjE1NDA1MzY4NjMsImV4cCI6MTU0MDU0MDQ2MywibmJmIjoxNTQwNTM2ODYzLCJqdGkiOiJWODdMZkRLeG1vTURDWk9YIiwic3ViIjoxMiwicHJ2IjoiODdlMGFmMWVmOWZkMTU4MTJmZGVjOTcxNTNhMTRlMGIwNDc1NDZhYSJ9.Z2uK4u2rUGADHyHr2Q6yvZ0KoPjToAgDYxYc9z884X0");
    localStorage.set('tokenType', 'bearer');
    localStorage.set('settings-tab', 'Account');
  }
