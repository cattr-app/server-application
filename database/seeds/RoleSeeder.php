<?php

use App\Models\Rule;
use App\Models\Role;
use App\User;
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

        Role::updateOrCreate(['id' => 1, 'name' => 'root']);
        Role::updateOrCreate(['id' => 2, 'name' => 'user']);
        Role::updateOrCreate(['id' => 3, 'name' => 'observer']);
        Role::updateOrCreate(['id' => 4, 'name' => 'client']);
        Role::updateOrCreate(['id' => 5, 'name' => 'manager']);
        Role::updateOrCreate(['id' => 255, 'name' => 'blocked']);

        $userAllow = [
            'project-report' => [
                'list',
                'projects',
            ],
            'projects' => [
                'list',
                'show',
            ],
            'roles' => [
                'allowed-rules',
            ],
            'screenshots' => [
                'create',
                'dashboard',
                'list',
                'remove',
                'show',
            ],
            'tasks' => [
                'create',
                'dashboard',
                'edit',
                'list',
                'remove',
                'show',
            ],
            'task-comment' => [
                'list',
                'create',
                'show',
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
                'edit',
                'list',
                'remove',
                'bulk-remove',
                'show',
            ],
            'time-use-report' => [
                'list',
            ],
            'users' => [
                'edit',
                'list',
                'show',
            ],
            'integration' => [
                'gitlab',
                'redmine',
            ],
        ];
        $observerAllow = [
            'projects' => [
                'list',
                'show',
            ],
            'roles' => [
                'allowed-rules',
            ],
            'screenshots' => [
                'dashboard',
                'list',
                'show'
            ],
            'tasks' => [
                'dashboard',
                'show',
                'list',
            ],
            'time' => [
                'project',
                'task',
                'task-user',
                'tasks',
                'total'
            ],
            'time-intervals' => [
                'list',
                'show',
                'bulk-remove',
            ],
            'users' => [
                'list',
                'relations',
                'show',
            ]
        ];
        $clientAllow = [
            'projects' => [
                'list',
                'relations',
                'show',
            ],
            'roles' => [
                'allowed-rules',
            ],
            'screenshots' => [
                'dashboard',
                'list',
                'show'
            ],
            'tasks' => [
                'dashboard',
                'list',
                'show',
            ],
            'time' => [
                'project',
                'task',
                'task-user',
                'tasks',
                'total',
            ],
            'time-intervals' => [
                'list',
                'show',
                'bulk-remove',
            ],
            'users' => [
                'list',
                'show',
                'relations',
            ],
            'integration' => [
                'gitlab',
                'redmine',
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
                'edit',
                'show',
            ],
            'roles' => [
                'list',
                'allowed-rules',
            ],
            'screenshots' => [
                'dashboard',
                'list',
                'edit',
                'remove',
                'show',
                'manager_access',
            ],
            'tasks' => [
                'dashboard',
                'edit',
                'list',
                'remove',
                'show',
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
                'list',
                'edit',
                'remove',
                'bulk-remove',
                'show',
                'manager_access'
            ],
            'time-use-report' => [
                'list',
            ],
            'users' => [
                'list',
                'edit',
                'bulk-edit',
                'relations',
                'show',
                'manager_access',
            ],
            'integration' => [
                'gitlab',
                'redmine',
            ],
        ];

        $this->addRules(1, Rule::getActionList());
        $this->addRules(2, $userAllow);
        $this->addRules(3, $observerAllow);
        $this->addRules(4, $clientAllow);
        $this->addRules(5, $managerAllow);

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
