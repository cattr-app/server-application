<?php

namespace Database\Seeders;

use App\Console\Commands\MakeAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

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
        Artisan::call(MakeAdmin::class);
    }
}
