<?php

namespace Tests\Feature\Tasks;

use App\User;
use Tests\Facades\ProjectFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class CreateTest
 * @package Tests\Feature\Tasks
 */
class CreateTest extends TestCase
{
    private const URI = 'v1/tasks/create';

    private const PRIORITY_ID = 2;

    /**
     * @var User
     */
    private $admin;
    /**
     * @var array
     */
    private $taskData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->taskData = array_merge(TaskFactory::getRandomTaskData(), [
            'project_id' => ProjectFactory::create()->id,
            'user_id' => UserFactory::create()->id
        ]);
    }

    public function test_create()
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->taskData);

        $response->assertApiSuccess();
        $this->assertDatabaseHas('tasks', $this->taskData);
        $this->assertDatabaseHas('tasks', $response->json('res'));
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
