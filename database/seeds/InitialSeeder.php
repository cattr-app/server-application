<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Class InitialSeeder
 */
class InitialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(PrioritiesSeeder::class);
        $this->call(StatusSeeder::class);
        $this->call(CompanyManagementSeeder::class);
    }
}
