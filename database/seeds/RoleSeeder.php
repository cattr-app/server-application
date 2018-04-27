<?php

use App\Models\Rule;
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


        DB::table('role')->insert(['id' => 1, 'name' => 'root']);
        DB::table('role')->insert(['id' => 2, 'name' => 'user']);
        DB::table('role')->insert(['id' => 255, 'name' => 'blocked']);



        foreach (Rule::getActionList() as $object => $actions) {
            foreach ($actions as $action => $action_name) {
                DB::table('rule')->insert([
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
