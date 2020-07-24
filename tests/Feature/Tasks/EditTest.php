<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class EditTest extends TestCase
{
    private const URI = 'tasks/edit';

    private User $admin;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->task = TaskFactory::create();
    }

    public function test_edit(): void
    {
        $this->task->description = 'New Description';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->task->toArray());

        $response->assertSuccess();
        $response->assertJson(['res' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', $this->task->toArray());
    }

    public function test_not_existing(): void
    {
        $this->task->id = Task::count() + 20;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->task->toArray());

        $response->assertNotFound();
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
