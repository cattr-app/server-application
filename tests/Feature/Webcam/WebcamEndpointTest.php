<?php

namespace Tests\Feature\Webcam;

use App\Contracts\WebcamScreenshotService;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Storage;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class WebcamEndpointTest extends TestCase
{

    private User $admin;
    private TimeInterval $interval;

    protected function setUp(): void
    {

        parent::setUp();

        Storage::fake();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->interval = IntervalFactory::forUser($this->admin)->create();

    }

    public function test_show_webcam_returns_image(): void
    {

        $service = app(WebcamScreenshotService::class);
        $path = $service->getWebcamPath($this->interval);
        Storage::put($path, UploadedFile::fake()->image('webcam.jpg')->getContent());

        $response = $this->actingAs($this->admin)->get("time-intervals/{$this->interval->id}/webcam");

        $response->assertOk();

    }

    public function test_show_webcam_returns_404_when_missing(): void
    {

        $response = $this->actingAs($this->admin)->get("time-intervals/{$this->interval->id}/webcam");

        $response->assertStatus(self::HTTP_NOT_FOUND);

    }

    public function test_show_webcam_thumbnail_returns_image(): void
    {

        $service = app(WebcamScreenshotService::class);
        $path = $service->getWebcamThumbPath($this->interval);
        Storage::put($path, UploadedFile::fake()->image('thumb.jpg')->getContent());

        $response = $this->actingAs($this->admin)->get("time-intervals/{$this->interval->id}/webcam-thumb");

        $response->assertOk();

    }

    public function test_show_webcam_thumbnail_returns_404_when_missing(): void
    {

        $response = $this->actingAs($this->admin)->get("time-intervals/{$this->interval->id}/webcam-thumb");

        $response->assertStatus(self::HTTP_NOT_FOUND);

    }

    public function test_show_webcam_unauthorized(): void
    {

        $response = $this->get("time-intervals/{$this->interval->id}/webcam");

        $response->assertUnauthorized();

    }

}
