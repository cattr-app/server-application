<?php

namespace Tests\Unit;

use App\Models\CronTaskWorkers;
use Tests\TestCase;

class CronTaskWorkersTest extends TestCase
{
    public function test_fillable_lists_attribute_names(): void
    {
        $fillable = (new CronTaskWorkers())->getFillable();

        $this->assertEqualsCanonicalizing(
            ['user_id', 'task_id', 'duration', 'offset', 'created_by_cron'],
            $fillable,
        );
    }

    public function test_mass_assignment_sets_attributes(): void
    {
        $worker = (new CronTaskWorkers())->fill([
            'user_id' => 1,
            'task_id' => 2,
            'duration' => 3,
            'offset' => 4,
            'created_by_cron' => true,
        ]);

        $this->assertSame(
            ['user_id' => 1, 'task_id' => 2, 'duration' => 3, 'offset' => 4, 'created_by_cron' => true],
            $worker->only(['user_id', 'task_id', 'duration', 'offset', 'created_by_cron']),
        );
    }
}
