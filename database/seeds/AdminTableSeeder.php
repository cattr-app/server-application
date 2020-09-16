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
    private const EMAIL = 'admin@example.com';
    private const PASSWORD = 'admin';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!User::where('is_admin', true)->exists()) {
            User::updateOrCreate([
                'email' => self::EMAIL
            ], [
                'url' => '',
                'company_id' => 1,
                'avatar' => '',
                'screenshots_active' => 1,
                'manual_time' => 1,
                'computer_time_popup' => 300,
                'blur_screenshots' => 0,
                'web_and_app_monitoring' => 1,
                'screenshots_interval' => 9,
                'active' => true,
                'password' => self::PASSWORD,
                'is_admin' => true,
                'role_id' => 2,
                'full_name' => 'Admin',
            ]);
        }
    }
}
