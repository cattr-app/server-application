<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use Tests\Factories\TaskFactory;
use Tests\Factories\UserFactory;
use App\User;
use Tests\TestCase;


/**
 * Class EditTest
 * @package Tests\Feature\Tasks
 */
class EditTest extends TestCase
{
    private const URI = 'v1/tasks/edit';

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

        $this->task = app(TaskFactory::class)->withUser()->create();
    }


    public function test_edit()
    {
        $this->task->description = 'New Description';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->task->toArray());

        $response->assertApiSuccess();
        $this->assertDatabaseHas('tasks', $this->task->toArray());
    }

    public function test_not_existing()
    {
        $this->task->id = Task::count() + 20;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->task->toArray());

        $response->assertApiError(404);
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
