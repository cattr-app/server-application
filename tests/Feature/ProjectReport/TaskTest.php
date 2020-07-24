<?php

namespace Tests\Feature\ProjectReport;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class TaskTest extends TestCase
{
    private const URI = 'project-report/list/tasks';

    private User $admin;

    private Collection $intervals;

    private int $duration = 0;
    private array $requestData;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->task = TaskFactory::forUser($this->admin)->create();

        $this->intervals = collect([
            IntervalFactory::forTask($this->task)->create(),
            IntervalFactory::forTask($this->task)->create(),
            IntervalFactory::forTask($this->task)->create(),
        ]);

        $this->requestData = [
            'start_at' => $this->intervals->min('start_at'),
            'end_at' => $this->intervals->max('end_at')->addMinute(),
            'uid' => $this->intervals->first()->user->id
        ];

        $this->duration = $this->intervals->sum(
            fn ($interval) => Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at)
        );
    }

    public function test_list_task(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI . '/' . $this->task->id, $this->requestData);

        $response->assertOk();
        $response->assertJsonFragment(['duration' => (string)$this->duration]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI . '/' . $this->task->id);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI . '/' . $this->task->id);

        $response->assertValidationError();
    }
}
