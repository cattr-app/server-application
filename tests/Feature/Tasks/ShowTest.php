<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Parsedown;
use phpDocumentor\Reflection\DocBlock\Description;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = 'tasks/show';

    private User $admin;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->task = TaskFactory::create();
    }

    public function test_show(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->task->only('id'));

        $this->task->description = (new Parsedown())->text($this->task->description);

        $response->assertOk();
        $response->assertJson($this->task->toArray());
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
