<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use App\Models\User;
use Tests\TestCase;


/**
 * Class ShowTest
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->task = TaskFactory::create();
    }

    public function test_show(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->task->only('id'));

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
