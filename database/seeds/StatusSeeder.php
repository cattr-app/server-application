<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Status::updateOrCreate(['id' => 1], ['name' => 'Open', 'active' => true]);
        Status::updateOrCreate(['id' => 2], ['name' => 'Closed', 'active' => false]);
    }
}
