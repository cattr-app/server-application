<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedStatus(1, ['name' => 'Open', 'active' => true]);
        $this->seedStatus(2, ['name' => 'Closed', 'active' => false]);
    }

    private function seedStatus(int $id, array $values): void
    {
        // `order` is unique and defaults to 0, so a new status needs its own position. The column does not exist yet
        // when the create_statuses_table migration runs this seeder, and existing statuses keep their position.
        if (Schema::hasColumn('statuses', 'order') && !Status::query()->whereKey($id)->exists()) {
            $values['order'] = Status::query()->max('order') + 1;
        }

        Status::updateOrCreate(['id' => $id], $values);
    }
}
