<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
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

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->task = TaskFactory::create();
    }


    public function test_edit()
    {
        $this->task->description = 'New Description';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->task->toArray());

        $response->assertSuccess();
        $this->assertDatabaseHas('tasks', $this->task->toArray());
    }

    public function test_not_existing()
    {
        $this->task->id = Task::count() + 20;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->task->toArray());

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
