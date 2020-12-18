<?php

namespace Database\Seeders;

use App\Models\Priority;
use Illuminate\Database\Seeder;

class PrioritiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Priority::updateOrCreate(['id' => 1], ['name' => 'Low']);
        Priority::updateOrCreate(['id' => 2], ['name' => 'Normal']);
        Priority::updateOrCreate(['id' => 3], ['name' => 'High']);
    }
}
