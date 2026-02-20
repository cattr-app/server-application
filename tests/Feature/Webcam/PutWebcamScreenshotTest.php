<?php

namespace Tests\Feature\Webcam;

use App\Contracts\WebcamScreenshotService;
use App\Enums\WebcamState;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Storage;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class PutWebcamScreenshotTest extends TestCase
{

    private User $admin;
    private TimeInterval $interval;

    protected function setUp(): void
    {

        parent::setUp();

        Storage::fake();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();

        $task = TaskFactory::forUser($this->admin)->create();
        $task->project->webcam_state = WebcamState::REQUIRED;
        $task->project->save();

        $this->interval = IntervalFactory::forUser($this->admin)->forTask($task)->create();

    }

    public function test_put_webcam_screenshot(): void
    {

        $response = $this->actingAs($this->admin)->postJson(
            "time-intervals/{$this->interval->id}/webcam",
            ['webcam_screenshot' => UploadedFile::fake()->image('webcam.jpg')]
        );

        $response->assertSuccess(204);

    }

    public function test_put_webcam_screenshot_conflict_when_exists(): void
    {

        $service = app(WebcamScreenshotService::class);
        $path = $service->getWebcamPath($this->interval);
        Storage::put($path, UploadedFile::fake()->image('webcam.jpg')->getContent());

        $response = $this->actingAs($this->admin)->postJson(
            "time-intervals/{$this->interval->id}/webcam",
            ['webcam_screenshot' => UploadedFile::fake()->image('webcam2.jpg')]
        );

        $response->assertStatus(self::HTTP_CONFLICT);

    }

    public function test_put_webcam_screenshot_forbidden_when_project_forbids(): void
    {

        $task = TaskFactory::forUser($this->admin)->create();
        $task->project->webcam_state = WebcamState::FORBIDDEN;
        $task->project->save();

        $interval = IntervalFactory::forUser($this->admin)->forTask($task)->create();

        $response = $this->actingAs($this->admin)->postJson(
            "time-intervals/{$interval->id}/webcam",
            ['webcam_screenshot' => UploadedFile::fake()->image('webcam.jpg')]
        );

        $response->assertStatus(self::HTTP_CONFLICT);

    }

    public function test_put_webcam_screenshot_without_file(): void
    {

        $response = $this->actingAs($this->admin)->postJson(
            "time-intervals/{$this->interval->id}/webcam",
            []
        );

        $response->assertValidationError();

    }

    public function test_put_webcam_screenshot_unauthorized(): void
    {

        $response = $this->postJson("time-intervals/{$this->interval->id}/webcam");

        $response->assertUnauthorized();

    }

}
