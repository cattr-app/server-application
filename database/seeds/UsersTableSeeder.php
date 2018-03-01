<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'full_name'              => 'Admin',
            'first_name'             => 'Ad',
            'last_name'              => 'Min',
            'email'                  => 'admin@example.com',
            'url'                    => '',
            'company_id'             => 1,
            'level'                  => 'admin',
            'payroll_access'         => 1,
            'billing_access'         => 1,
            'avatar'                 => '',
            'screenshots_active'     => 1,
            'manual_time'            => 0,
            'permanent_tasks'        => 0,
            'computer_time_popup'    => 300,
            'poor_time_popup'        => '',
            'blur_screenshots'       => 0,
            'web_and_app_monitoring' => 1,
            'webcam_shots'           => 0,
            'screenshots_interval'   => 9,
            'user_role_value'        => '',
            'active'                 => 'active',
            'password'               => bcrypt('admin'),
        ]);
    }
}
