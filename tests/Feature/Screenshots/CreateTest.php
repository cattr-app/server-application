<?php

namespace Tests\Feature\Screenshots;

use App\Models\TimeInterval;
use App\Models\User;
use Faker\Factory;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Storage;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private const URI = '/screenshots/create';

    private User $admin;
    private File $screenshotFile;
    private TimeInterval $interval;


    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        Storage::fake();

        $screenshotName = Factory::create()->firstName . '.jpg';
        $this->screenshotFile = UploadedFile::fake()->image($screenshotName);

        $this->interval = IntervalFactory::create();
    }

    public function test_create(): void
    {
        $requestData = ['time_interval_id' => $this->interval->id, 'screenshot' => $this->screenshotFile];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertOk();
        $this->assertDatabaseHas('screenshots', $response->json('screenshot'));
        Storage::assertExists('uploads/screenshots/' . basename($response->json('screenshot.path')));
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
