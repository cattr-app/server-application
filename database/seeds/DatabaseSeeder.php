<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Class DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(InitialSeeder::class);
        $this->call(ScreenshotSeeder::class);
        $this->call(AdminTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(TaskListSeeder::class);
        $this->call(StatisticsSeeder::class);
    }
}
