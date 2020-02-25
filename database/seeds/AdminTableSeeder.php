<?php

use App\Models\User;
use App\Models\Rule;
use Illuminate\Database\Seeder;
use Symfony\Component\Console\Output\ConsoleOutput;


/**
 * Class AdminTableSeeder
 */
class AdminTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $login = 'admin@example.com';
        $pass = 'admin';

        if (!User::where('is_admin', true)->exists()) {
            $this->command->getOutput()->writeln('<fg=yellow>Create admin user</>');

            /** @var User $admin */
            $admin = User::query()->updateOrCreate([
                'full_name' => 'Admin',
                'email' => $login,
                'url' => '',
                'company_id' => 1,
                'payroll_access' => 1,
                'billing_access' => 1,
                'avatar' => '',
                'screenshots_active' => 1,
                'manual_time' => 0,
                'permanent_tasks' => 0,
                'computer_time_popup' => 300,
                'poor_time_popup' => '',
                'blur_screenshots' => 0,
                'web_and_app_monitoring' => 1,
                'webcam_shots' => 0,
                'screenshots_interval' => 9,
                'active' => true,
                'password' => bcrypt($pass),
                'is_admin' => true,
                'role_id' => 2,
            ]);

            $this->command->getOutput()->writeln('<fg=green>Admin user has been created</>');
        }
    }
}
