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
        Role::updateOrCreate(['id' => 255, 'name' => 'blocked']);

        foreach (Rule::getActionList() as $object => $actions) {
            foreach ($actions as $action => $action_name) {
                Rule::updateOrCreate([
                    'role_id' => 1,
                    'object' => $object,
                    'action' => $action,
                    'allow' => true,
                ]);
            }
        }

        $this->command->getOutput()->writeln('<fg=green>Base roles has been created</>');
    }
}
