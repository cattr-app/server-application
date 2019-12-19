<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use Tests\Factories\TaskFactory;
use Tests\Factories\UserFactory;
use App\User;
use Tests\TestCase;

/**
 * Class RemoveTest
 * @package Tests\Feature\Tasks
 */
class RemoveTest extends TestCase
{
    private const URI = 'v1/tasks/remove';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var Task
     */
    private $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = app(UserFactory::class)
            ->withTokens()
            ->asAdmin()
            ->create();

        $this->task = app(TaskFactory::class)->create();
    }

    public function test_remove()
    {
        $this->assertDatabaseHas('tasks', $this->task->toArray());

        $response = $this->actingAs($this->admin)->postJson(self::URI, ['id' => $this->task->id]);

        $response->assertApiSuccess();
        $this->assertSoftDeleted('tasks', ['id' => $this->task->id]);
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
}
