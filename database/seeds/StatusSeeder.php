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
        $statuses = [
            ['name' => 'Open', 'active' => true, 'order' => 1],
            ['name' => 'Closed', 'active' => false, 'order' => 2],
        ];

        foreach ($statuses as $status) {
            Status::updateOrCreate(
                ['name' => $status['name']],
                ['active' => $status['active'], 'order' => $status['order']]
            );
        }
    }
}
