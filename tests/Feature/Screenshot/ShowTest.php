<?php

namespace Tests\Feature\Screenshot;

use App\Models\Screenshot;
use App\User;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = '/v1/screenshots/show';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var Screenshot
     */
    private $screenshot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->screenshot = ScreenshotFactory::create();
    }

    public function test_show()
    {
        $this->assertDatabaseHas('screenshots', $this->screenshot->toArray());

        $response = $this->actingAs($this->admin)->get(self::URI . '?id=' . $this->screenshot->id);
        $response->assertOk();
    }

    public function test_unauthorized()
    {
        $response = $this->get(self::URI);
        $response->assertUnauthorized();
    }

    public function test_without_params()
    {
        $response = $this->actingAs($this->admin)->get(self::URI);
        $response->assertValidationError();
    }
}
