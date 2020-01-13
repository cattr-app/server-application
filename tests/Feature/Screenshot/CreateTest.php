<?php

namespace Tests\Feature\Screenshot;

use App\User;
use Illuminate\Support\Facades\Storage;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private const URI = '/v1/screenshots/create';

    /**
     * @var User
     */
    private $admin;
    /**
     * @var array
     */
    private $screenshotData;
    /**
     * @var User
     */
    private $commonUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->commonUser = UserFactory::withTokens()->asUser()->create();
        $this->screenshotData = ScreenshotFactory::getRandomScreenshotData();
    }

    public function test_create(): void
    {
        $this->assertDatabaseMissing('screenshots', $this->screenshotData);

        $screenshot = ScreenshotFactory::getImage();

        $response = $this->actingAs($this->admin)->postJson(
            self::URI,
            [
                'time_interval_id' => $this->screenshotData['time_interval_id'],
                'screenshot' => $screenshot
            ]
        );
        $response->assertOk();
        $response->assertJson(['success' => "true"]);

        $this->assertDatabaseHas('screenshots', $response->json()['screenshot']);

        $data = explode('/', $response->json()['screenshot']['path']);
        $name = $data[count($data) - 1];

        Storage::disk()->assertExists('uploads/screenshots/' . $name);
        Storage::disk()->deleteDirectory('uploads/');
        Storage::disk()->assertMissing('uploads/screenshots/' . $name);
    }

    public function test_common_create()
    {
        $this->assertDatabaseMissing('screenshots', $this->screenshotData);

        $screenshot = ScreenshotFactory::getImage();

        $response = $this->actingAs($this->commonUser)->postJson(
            self::URI,
            [
                'time_interval_id' => $this->screenshotData['time_interval_id'],
                'screenshot' => $screenshot
            ]
        );
        $response->assertOk();
        $response->assertJson(['success' => "true"]);

        $data = explode('/', $response->json()['screenshot']['path']);
        $name = $data[count($data) - 1];

        Storage::disk()->assertExists('uploads/screenshots/' . $name);
        Storage::disk()->deleteDirectory('uploads/');
        Storage::disk()->assertMissing('uploads/screenshots/' . $name);
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
