<?php

namespace Tests\Feature\Screenshots;

use App\Models\Screenshot;
use App\Models\User;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = '/screenshots/show';

    private User $admin;
    private Screenshot $screenshot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->screenshot = ScreenshotFactory::fake()->create();
    }

    public function test_show(): void
    {
        $this->assertDatabaseHas('screenshots', $this->screenshot->toArray());

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->screenshot->only('id'));
        $response->assertOk();
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertValidationError();
    }
}
