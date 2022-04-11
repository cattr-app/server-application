<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Class AdminTableSeeder
 */
class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'is_admin' => true,
            'role_id' => 2,
            'password' => 'admin',
            'email' => 'admin@example.com',
        ]);
    }
}
