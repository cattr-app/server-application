<?php

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    private const USERS_AMOUNT = 10;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->getOutput()->writeln('<fg=yellow>Creating random users...</>');

        $faker = Faker\Factory::create();

        for ($i = 1; $i <= self::USERS_AMOUNT; ++$i) {
            User::create([
                'full_name' => $faker->name,
                'email' => $faker->email,
                'url' => '',
                'company_id' => 1,
                'payroll_access' => 0,
                'billing_access' => 0,
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
                'password' => bcrypt('password'),
                'role_id' => 2,
            ]);
            $this->command->getOutput()->writeln("<fg=green>User #{$i}/50 has been created</>");
        }
        $this->command->getOutput()->writeln('<fg=green>10 Users has been created!</>');
    }
}
