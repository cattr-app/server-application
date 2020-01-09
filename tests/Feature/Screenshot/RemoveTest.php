<?php

namespace Tests\Feature\Screenshot;

use App\Models\Screenshot;
use App\User;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    const URI = '/v1/screenshots/remove';

    /**
     * @var User
     */
    private $admin;
    /**
     * @var Screenshot
     */
    private $screenshot;
    /**
     * @var User
     */
    private $commonUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->commonUser = UserFactory::withTokens()->asUser()->create();

        $this->screenshot = ScreenshotFactory::create();
    }

    public function test_remove(): void
    {
        $this->assertDatabaseHas('screenshots', $this->screenshot->toArray());

        $response = $this->actingAs($this->admin)->postJson(self::URI, ['id' => $this->screenshot->id]);

        $response->assertSeeText('deleted');
        $this->assertSoftDeleted('screenshots', ['id' => $this->screenshot->id]);
    }

    public function test_common_remove(): void
    {
        $this->assertDatabaseHas('screenshots', $this->screenshot->toArray());

        $response = $this->actingAs($this->commonUser)->postJson(self::URI, ['id' => $this->screenshot->id]);

        $response->assertSeeText('deleted');
        $this->assertSoftDeleted('screenshots', ['id' => $this->screenshot->id]);
    }

    public function test_unauthorized()
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
