<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CountTest extends TestCase
{
    private const URI = 'tasks/count';

    private const TASKS_AMOUNT = 10;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        TaskFactory::createMany(self::TASKS_AMOUNT);
    }

    public function test_count(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson(['total' => Task::count()]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
