<?php

namespace Tests\Feature\Screenshots;

use App\Models\TimeInterval;
use App\User;
use Faker\Factory;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Facades\ScreenshotFactory;
use Tests\Facades\UserFactory;
use Tests\Facades\IntervalFactory;
use Tests\TestCase;

/**
 * Class CreateTest
 */
class CreateTest extends TestCase
{
    private const URI = '/v1/screenshots/create';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var File
     */
    private $screenshot;

    /**
     * @var TimeInterval
     */
    private $interval;

    /**
     * @var string
     */
    private $screenshotName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        Storage::fake();

        $this->screenshotName = Factory::create()->firstName . '.jpg';
        $this->screenshot = UploadedFile::fake()->image($this->screenshotName);

        $this->interval = IntervalFactory::create();

    }

    public function test_create(): void
    {
        $requestData = ['time_interval_id' => $this->interval->id, 'screenshot' => $this->screenshot];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertSuccess();
        $this->assertDatabaseHas('screenshots', $response->json('screenshot'));
        Storage::assertExists('uploads/screenshots/' . basename($response->json('screenshot.path')));

        //TODO find out what's wrong with thumbnails
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
