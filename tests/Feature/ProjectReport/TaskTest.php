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
    private const URI = '/api/report/project';

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
            'start_at' => Carbon::parse($this->intervals->min('start_at'))->startOfDay()->toDateTimeString(),
            'end_at' => Carbon::parse($this->intervals->max('end_at'))->endOfDay()->toDateTimeString(),
            'users' => $this->intervals->pluck('user_id')->unique()->values()->all(),
            'projects' => [$this->task->project_id]
        ];

        $this->duration = $this->intervals->sum(
            fn ($interval) => (int) Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at, true)
        );
    }

    public function test_list_task(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->requestData);

        $response->assertOk();
        $projects = collect($response->json('data'));
        $tasks = $projects->flatMap(static fn (array $project) => collect($project['users'])
            ->flatMap(static fn (array $user) => $user['tasks']));

        $this->assertSame($this->duration, (int) $tasks->where('id', $this->task->id)->sum('time'));
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI, $this->requestData);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertStatus(self::HTTP_UNPROCESSABLE_ENTITY);
    }
}
