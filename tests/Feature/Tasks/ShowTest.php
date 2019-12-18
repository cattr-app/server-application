<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use Tests\Factories\TaskFactory;
use Tests\Factories\UserFactory;
use App\User;
use Tests\TestCase;


/**
 * Class ShowTest
 * @package Tests\Feature\Projects
 */
class ShowTest extends TestCase
{
    private const URI = 'v1/tasks/show';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var Task
     */
    private $task;

    public function test_show()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, ['id' => $this->task->id]);

        $response->assertOk();
        $response->assertJson($this->task->toArray());
    }

    public function test_unauthorized()
    {
        $response = $this->postJson(self::URI);

        $response->assertApiError(401);
    }

    public function test_without_params()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertApiError(400, true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = app(UserFactory::class)
            ->withTokens()
            ->asAdmin()
            ->create();

        $this->task = app(TaskFactory::class)->create();
    }
}
