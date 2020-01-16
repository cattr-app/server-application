<?php

namespace Tests\Feature\Screenshots;

use App\Models\Screenshot;
use App\User;
use Illuminate\Support\Facades\Storage;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'v1/screenshots/list';

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

        ScreenshotFactory::withRandomRelations()->createMany(self::SCREENSHOTS_AMOUNT);
    }

    public function test_list()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertOk();
        $response->assertJson(Screenshot::all()->toArray());
    }

    public function test_unauthorized()
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
