<?php

use App\Models\Rule;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Symfony\Component\Console\Output\ConsoleOutput;


class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->getOutput()->writeln('<fg=yellow>Add base roles</>');

        Role::updateOrCreate(['id' => 1], ['name' => 'manager']);
        Role::updateOrCreate(['id' => 2], ['name' => 'user']);
        Role::updateOrCreate(['id' => 3], ['name' => 'auditor']);

        $userAllow = [
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
            'integration' => [
                'gitlab',
                'redmine',
                'jira',
            ],
        ];
        $auditorAllow = [
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
            'integration' => [
                'gitlab',
                'redmine',
                'jira',
            ],
        ];
        $managerAllow = [
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
            'integration' => [
                'gitlab',
                'redmine',
                'jira',
            ],
        ];

        $this->addRules(1, $managerAllow);
        $this->addRules(2, $userAllow);
        $this->addRules(3, $auditorAllow);

        $rules = collect(Rule::getActionList());
        foreach (Rule::all() as $rule) {
            if ($rules->has($rule['object'])) {
                if (collect($rules[$rule['object']])->has($rule['action'])) {
                    continue;
                }
            }
            $this->command->getOutput()->writeln("<fg=red>{$rule['object']} {$rule['action']} Not Found (Removed)</>");
            $rule->forceDelete();
        }

        $this->command->getOutput()->writeln('<fg=green>Base roles has been created</>');
    }

    private function addRules($role_id, $allowList)
    {
        Rule::where(['role_id' => $role_id])->forceDelete();
        foreach ($allowList as $object => $actions) {
            foreach ($actions as $action => $action_name) {
                Rule::updateOrCreate([
                    'role_id' => $role_id,
                    'object' => $object,
                    'action' => !is_int($action) ? $action : $action_name,
                    'allow' => true,
                ]);
            }
        }
        return true;
    }
}
