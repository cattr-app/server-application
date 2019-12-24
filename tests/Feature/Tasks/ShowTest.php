<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use Tests\Factories\Facades\TaskFactory;
use Tests\Factories\Facades\UserFactory;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->task = TaskFactory::create();
    }

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
}
