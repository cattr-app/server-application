<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use App\User;
use Tests\TestCase;

/**
 * Class RemoveTest
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

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->task = TaskFactory::create();
    }

    public function test_remove(): void
    {
        $this->assertDatabaseHas('tasks', $this->task->toArray());

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->task->only('id'));

        $response->assertSuccess();
        $this->assertSoftDeleted('tasks', $this->task->only('id'));
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
