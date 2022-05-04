<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::updateOrCreate(['id' => 1], ['name' => 'manager']);
        Role::updateOrCreate(['id' => 2], ['name' => 'user']);
        Role::updateOrCreate(['id' => 3], ['name' => 'auditor']);
    }
}
