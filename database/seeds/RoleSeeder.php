<?php

use App\Models\Rule;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{

    private const USER_ALLOW = [
        'project-report' => [
            'list',
            'projects',
        ],
        'projects' => [],
        'roles' => [
            'allowed-rules',
        ],
        'screenshots' => [
            'create',
            'bulk-create',
        ],
        'tasks' => [
            'create',
            'edit',
            'remove',
        ],
        'time' => [
            'project',
            'task',
            'task-user',
            'tasks',
            'total',
        ],
        'time-duration' => [
            'list',
        ],
        'time-intervals' => [
            'create',
            'bulk-create',
            'bulk-edit',
            'bulk-remove',
        ],
        'time-use-report' => [
            'list',
        ],
        'users' => [
        ],
        'company-settings' => [
            'index',
        ],
        'integration' => [
            'gitlab',
            'redmine',
            'jira',
            'trello',
        ],
    ];
    private const AUDITOR_ALLOW = [
        'dashboard' => [
            'manager_access',
        ],
        'project-report' => [
            'list',
            'projects',
            'manager_access',
        ],
        'projects' => [
            'list',
            'show',
        ],
        'roles' => [
            'list',
            'allowed-rules',
        ],
        'screenshots' => [
            'manager_access',
            'dashboard',
            'list',
            'show',
            'create',
            'bulk-create',
        ],
        'tasks' => [
            'dashboard',
            'list',
            'show',
            'create',
        ],
        'time' => [
            'project',
            'task',
            'task-user',
            'tasks',
            'total'
        ],
        'time-duration' => [
            'list',
        ],
        'time-intervals' => [
            'manager_access',
            'list',
            'show',
            'create',
            'bulk-create',
            'bulk-edit',
            'bulk-remove',
        ],
        'time-use-report' => [
            'list',
        ],
        'users' => [
            'manager_access',
            'list',
            'show',
        ],
        'company-settings' => [
            'index',
        ],
        'integration' => [
            'gitlab',
            'redmine',
            'jira',
            'trello',
        ],
    ];
    private const MANAGER_ALLOW = [
        'dashboard' => [
            'manager_access',
        ],
        'project-report' => [
            'list',
            'projects',
            'manager_access',
        ],
        'projects' => [
            'list',
            'show',
            'create',
            'edit',
            'remove',
        ],
        'roles' => [
            'list',
            'allowed-rules',
        ],
        'screenshots' => [
            'manager_access',
            'dashboard',
            'list',
            'show',
            'create',
            'bulk-create',
            'edit',
            'remove',
        ],
        'tasks' => [
            'dashboard',
            'list',
            'show',
            'create',
            'edit',
            'remove',
        ],
        'time' => [
            'project',
            'task',
            'task-user',
            'tasks',
            'total'
        ],
        'time-duration' => [
            'list',
        ],
        'time-intervals' => [
            'manager_access',
            'list',
            'show',
            'create',
            'bulk-create',
            'bulk-edit',
            'edit',
            'bulk-edit',
            'remove',
            'bulk-remove',
        ],
        'time-use-report' => [
            'list',
            'manager_access',
        ],
        'users' => [
            'manager_access',
            'list',
            'show',
            'edit',
            'bulk-edit',
        ],
        'company-settings' => [
            'index',
        ],
        'integration' => [
            'gitlab',
            'redmine',
            'jira',
            'trello',
        ],
        'email-reports' => [
            'list',
            'show',
            'edit',
            'remove',
            'create',
            'count',
        ],
        'invoices' => [
            'list',
        ]
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::updateOrCreate(['id' => 1], ['name' => 'manager']);
        Role::updateOrCreate(['id' => 2], ['name' => 'user']);
        Role::updateOrCreate(['id' => 3], ['name' => 'auditor']);

        $this->addRules(1, self::MANAGER_ALLOW);
        $this->addRules(2, self::USER_ALLOW);
        $this->addRules(3, self::AUDITOR_ALLOW);

        $rules = collect(Rule::getActionList());
        foreach (Rule::all() as $rule) {
            if ($rules->has($rule['object']) && collect($rules[$rule['object']])->has($rule['action'])) {
                continue;
            }

            $this->command->getOutput()->writeln(
                "<fg=yellow>{$rule['object']} {$rule['action']} Not Found (Removed)</>"
            );
            $rule->forceDelete();
        }
    }

    private function addRules($roleId, $allowList): bool
    {
        Rule::where(['role_id' => $roleId])->forceDelete();
        foreach ($allowList as $object => $actions) {
            foreach ($actions as $action => $actionName) {
                Rule::updateOrCreate([
                    'role_id' => $roleId,
                    'object' => $object,
                    'action' => !is_int($action) ? $action : $actionName,
                    'allow' => true,
                ]);
            }
        }
        return true;
    }
}
