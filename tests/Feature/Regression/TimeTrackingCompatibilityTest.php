<?php

namespace Tests\Feature\Regression;

use App\Enums\Role;
use App\Models\CronTaskWorkers;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Tests\Facades\TaskFactory;
use Tests\TestCase;

class TimeTrackingCompatibilityTest extends TestCase
{
    private User $user;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role_id' => Role::ADMIN->value]);
        $this->task = TaskFactory::refresh()->forUser($this->user)->create();

        // Avoid depending on how an empty cron_task_workers row is created;
        // the contract under test is the elapsed-seconds accounting.
        CronTaskWorkers::query()->forceCreate([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'duration' => 0,
            'offset' => 0,
        ]);
    }

    public function test_creating_and_editing_an_interval_preserves_positive_worker_offset(): void
    {
        $interval = $this->createInterval('2026-10-20 10:00:00', '2026-10-20 10:01:30');
        $this->assertSame(90, $this->workerOffset());

        $interval->update(['end_at' => '2026-10-20 10:03:00']);
        $this->assertSame(180, $this->workerOffset());
    }

    public function test_time_total_endpoint_returns_the_sum_of_elapsed_seconds(): void
    {
        $this->createInterval('2026-10-20 10:00:00', '2026-10-20 10:01:00');
        $this->createInterval('2026-10-20 10:03:00', '2026-10-20 10:05:05');

        $token = $this->user->createToken('stage-a-time-regression')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/time/total', [
                'start_at' => '2026-10-20 09:00:00',
                'end_at' => '2026-10-20 11:00:00',
                'user_id' => $this->user->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.time', 185);
    }

    private function createInterval(string $start, string $end): TimeInterval
    {
        return TimeInterval::query()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'start_at' => $start,
            'end_at' => $end,
            'is_manual' => true,
            'activity_fill' => 0,
            'mouse_fill' => 0,
            'keyboard_fill' => 0,
        ]);
    }

    private function workerOffset(): int
    {
        return (int) CronTaskWorkers::query()
            ->where('user_id', $this->user->id)
            ->where('task_id', $this->task->id)
            ->firstOrFail()
            ->offset;
    }
}
