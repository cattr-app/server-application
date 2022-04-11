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
        $this->call(AdminSeeder::class);
        $this->call(DemoDataSeeder::class);
    }
}
