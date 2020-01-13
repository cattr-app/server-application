<?php


namespace Tests\Feature\Interval;


use App\Models\Task;
use App\Models\TimeInterval;
use App\User;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class EditTest extends TestCase
{
    private const URI = 'v1/time-intervals/edit';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var TimeInterval
     */
    private $interval;

    /**
     * @var array
     */
    private $randomDataInterval;

    /**
     * @var Task
     */
    private $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->interval = IntervalFactory::create();
        $this->randomDataInterval = IntervalFactory::getRandomIntervalData();
        $this->task = TaskFactory::create();
    }

    public function test_edit()
    {
        $originInterval = $this->interval->toArray();

        $this->assertDatabaseHas('time_intervals', $originInterval);
        $requestData = array_merge(
            $this->randomDataInterval,
            [
                'id' => $originInterval['id'],
                'user_id' => $this->admin->id,
                'task_id' => $this->task->id,

            ]
        );
        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertOk();

        $this->assertDatabaseHas('time_intervals', $response->json()['res']);
    }

    public function test_not_existing_interval()
    {
        $this->interval->id++;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->interval->toArray());

        $response->assertItemNotFound();
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
