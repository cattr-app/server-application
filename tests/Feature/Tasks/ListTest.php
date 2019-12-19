<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use Tests\Factories\TaskFactory;
use Tests\Factories\UserFactory;
use App\User;
use Tests\TestCase;

/**
 * Class ListTest
 * @package Tests\Feature\Tasks
 */
class ListTest extends TestCase
{
    private const URI = 'v1/tasks/list';

    private const TASKS_AMOUNT = 10;

    /**
     * @var User
     */
    private $admin;
    private $commonUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = app(UserFactory::class)
            ->withTokens()
            ->asAdmin()
            ->create();

        $this->commonUser = app(UserFactory::class)
            ->withTokens()
            ->create();

        app(TaskFactory::class)->createMany(self::TASKS_AMOUNT);
    }

    public function test_list()
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson(Task::all()->toArray());
    }

    public function test_unauthorized()
    {
        $response = $this->getJson(self::URI);

        $response->assertApiError(401);
    }

    public function test_common_user()
    {
        $response = $this->actingAs($this->commonUser)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson([]);
    }
}
