<?php

namespace Tests\Feature\Interval;

use App\Models\Task;
use App\User;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CreateTest extends TestCase
{
    /**
     * endpoint
     */
    const URI = 'v1/time-intervals/create';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var array
     */
    private $intervalData;

    /**
     * @var User
     */
    private $commonUser;

    /**
     * @var Task
     */
    private $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->commonUser = UserFactory::withTokens()->asUser()->create();
        $this->task = TaskFactory::create();
        $this->intervalData = array_merge(
            IntervalFactory::getRandomIntervalData(),
            ['task_id' => $this->task->id, 'user_id' => $this->admin->id]
        );
    }

    public function test_create(): void
    {
        $this->assertDatabaseMissing('time_intervals', $this->intervalData);

        $response = $this->actingAs($this->admin)->postJson(
            self::URI,
            $this->intervalData
        );
        $response->assertOk();
        $response->assertJson(['success' => "true"]);

        $this->assertDatabaseHas('time_intervals', $response->json()['interval']);
    }

    public function test_common_create(): void
    {
        $this->assertDatabaseMissing('time_intervals', $this->intervalData);
        $response = $this->actingAs($this->commonUser)->postJson(
            self::URI,
            array_merge($this->intervalData, ['task_id' => $this->task->id, 'user_id' => $this->commonUser->id])
        );
        $response->assertOk();

        $this->assertDatabaseHas('time_intervals', $response->json()['interval']);
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
