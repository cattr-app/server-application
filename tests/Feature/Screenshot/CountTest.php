<?php

namespace Tests\Feature\Screenshot;

use App\Models\Screenshot;
use App\User;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CountTest extends TestCase
{
    private const URI = 'v1/screenshots/count';

    private const SCREENSHOTS_AMOUNT = 10;

    /**
     * @var User
     */
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        ScreenshotFactory::createMany(self::SCREENSHOTS_AMOUNT);
    }

    public function test_count()
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertSuccess();
        $response->assertJson(['total' => Screenshot::count()]);
    }

    public function test_unauthorized()
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
