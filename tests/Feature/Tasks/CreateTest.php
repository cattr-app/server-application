<?php

namespace Tests\Feature\Tasks;

use App\Models\User;
use Tests\Facades\ProjectFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;


class CreateTest extends TestCase
{
    private const URI = 'v1/tasks/create';

    private User $admin;
    /**
     * @var array
     */
    private $taskData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->taskData = array_merge(TaskFactory::createRandomModelData(), [
            'project_id' => ProjectFactory::create()->id,
            'user_id' => UserFactory::create()->id
        ]);
    }

    public function test_create(): void
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->taskData);

        $response->assertSuccess();
        $this->assertDatabaseHas('tasks', $this->taskData);
        $this->assertDatabaseHas('tasks', $response->json('res'));
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
