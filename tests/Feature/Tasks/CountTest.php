<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use Tests\Factories\Facades\TaskFactory;
use Tests\Factories\Facades\UserFactory;
use App\User;
use Tests\TestCase;

/**
 * Class CountTest
 * @package Tests\Feature\Tasks
 */
class CountTest extends TestCase
{
    private const URI = 'v1/tasks/count';

    private const TASKS_AMOUNT = 10;

    /**
     * @var User
     */
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        TaskFactory::createMany(self::TASKS_AMOUNT);
    }

    public function test_count()
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertApiSuccess();
        $response->assertJson(['total' => Task::count()]);
    }

    public function test_unauthorized()
    {
        $response = $this->getJson(self::URI);

        $response->assertApiError(401);
    }
}
