<?php

use App\Models\Role;
use App\Models\Rule;
use App\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->getOutput()->writeln('<fg=yellow>Create default roles</>');

        Role::create(['id' => 1, 'name' => 'root']);
        Role::create(['id' => 2, 'name' => 'user']);
        Role::create(['id' => 255, 'name' => 'blocked']);

        /**
         * @var string $object
         * @var array $actions
         */
        foreach (Rule::getActionList() as $object => $actions) {
            foreach ($actions as $action => $action_name) {
                Rule::create([
                    'role_id' => 1,
                    'object' => $object,
                    'action' => $action,
                    'allow' => true,
                ]);
            }
        }

        foreach (User::all() as $user) {
            $user->role_id = 1;
            $user->save();
        }

        $this->command->getOutput()->writeln('<fg=green>Roles has been created</>');
    }
}
