<?php

namespace Tests\Feature\Screenshots;

use App\Models\Screenshot;
use App\Models\User;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'screenshots/list';

    private const SCREENSHOTS_AMOUNT = 10;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        ScreenshotFactory::fake()->withRandomRelations()->createMany(self::SCREENSHOTS_AMOUNT);
    }

    public function test_list(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertOk();
        $response->assertJson(Screenshot::all()->toArray());
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
