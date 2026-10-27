<?php

namespace Tests\Feature\Status;

use App\Models\Status;
use Database\Seeders\StatusSeeder;
use Tests\TestCase;

class StatusSeederTest extends TestCase
{
    public function test_seeds_both_statuses_into_empty_table(): void
    {
        // cattr:reset truncates the table before running InitialSeeder; DELETE keeps the test transactional.
        Status::query()->delete();

        (new StatusSeeder())->run();

        $this->assertSame(
            [
                ['name' => 'Open', 'active' => true, 'order' => 1],
                ['name' => 'Closed', 'active' => false, 'order' => 2],
            ],
            Status::query()->orderBy('order')->get(['name', 'active', 'order'])->toArray(),
        );
    }

    public function test_keeps_order_of_existing_statuses(): void
    {
        $before = Status::query()->orderBy('id')->pluck('order', 'id')->all();

        (new StatusSeeder())->run();

        $this->assertSame($before, Status::query()->orderBy('id')->pluck('order', 'id')->all());
        $this->assertDatabaseHas('statuses', ['id' => 1, 'name' => 'Open', 'active' => true]);
        $this->assertDatabaseHas('statuses', ['id' => 2, 'name' => 'Closed', 'active' => false]);
    }
}
