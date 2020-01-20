<?php

namespace Tests\Feature\TimeIntervals;

use App\Models\Task;
use App\User;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private const URI = 'v1/time-intervals/create';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var array
     */
    private $intervalData;

    /**
     * @var Task
     */
    private $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->task = TaskFactory::forUser($this->admin)->create();

        $this->intervalData = array_merge(
            IntervalFactory::getRandomIntervalData(),
            ['task_id' => $this->task->id, 'user_id' => $this->task->user_id]
        );
    }

    public function test_create(): void
    {
        $this->assertDatabaseMissing('time_intervals', $this->intervalData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->intervalData);

        $response->assertSuccess();
        $this->assertDatabaseHas('time_intervals', $this->intervalData);
    }

    public function test_unauthorized()
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
