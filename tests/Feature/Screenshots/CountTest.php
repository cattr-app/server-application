<?php

namespace Tests\Feature\Screenshots;

use App\Models\Screenshot;
use App\User;
use Illuminate\Support\Facades\Storage;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class CountTest
 */
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

        Storage::fake();

        ScreenshotFactory::createMany(self::SCREENSHOTS_AMOUNT);
    }

    public function test_count(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertSuccess();
        $response->assertJson(['total' => Screenshot::count()]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
