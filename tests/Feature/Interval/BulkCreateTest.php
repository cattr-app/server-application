<?php


namespace Tests\Feature\Interval;


use App\Models\Task;
use App\User;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class BulkCreateTest extends TestCase
{
    private const URI = 'v1/time-intervals/bulk-create';
    private const INTERVALS_AMOUNT = 10;

    /**
     * @var User
     */
    private $admin;

    /**
     * @var User
     */
    private $commonUser;

    /**
     * @var Task
     */
    private $task;

    /**
     * @var array
     */
    private $intervalData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->commonUser = UserFactory::withTokens()->asUser()->create();
        $this->task = TaskFactory::createMany(self::INTERVALS_AMOUNT);
        $this->intervalData = IntervalFactory::createMany(self::INTERVALS_AMOUNT);
    }

    //todo добавить создание Multi Mocks без добавления в БД
    public function test_bulk_create()
    {
        $data = $this->intervalData->toArray();

        $this->assertDatabaseMissing('time_intervals', $data[0]);

        $response = $this->actingAs($this->admin)->postJson(
            self::URI,
            array_merge($this->intervalData, ['task_id' => $this->task->id, 'user_id' => $this->admin->id])
        );
        $response->assertOk();
        $response->assertJson(['success' => "true"]);

        $this->assertDatabaseHas('time_intervals', $response->json()['interval']);
    }

}
