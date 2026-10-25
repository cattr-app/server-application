<?php

namespace Tests\Feature\TimeIntervals;

use App\Contracts\ScreenshotService;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Screenshots uploaded by the desktop app must be stored together with their thumbnail.
 *
 * QUEUE_CONNECTION is "sync" in phpunit.xml, so GenerateScreenshotThumbnail runs inside the request.
 */
class ScreenshotUploadTest extends TestCase
{
    private const CREATE_URI = 'time-intervals/create';
    private const THUMB_WIDTH = 280;

    private User $admin;
    private Task $task;
    private ScreenshotService $screenshotService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->task = TaskFactory::forUser($this->admin)->create();
        $this->screenshotService = app(ScreenshotService::class);
    }

    public function test_create_with_screenshot_stores_screenshot_and_thumbnail(): void
    {
        $intervalData = IntervalFactory::createRandomModelData();
        $intervalData['task_id'] = $this->task->id;
        $intervalData['user_id'] = $this->admin->id;

        $response = $this->actingAs($this->admin)->post(
            self::CREATE_URI,
            $intervalData + ['screenshot' => UploadedFile::fake()->image('screenshot.png', 1280, 720)],
            ['Accept' => 'application/json'],
        );

        $response->assertOk();

        $interval = TimeInterval::whereUserId($this->admin->id)->whereTaskId($this->task->id)->sole();

        $this->assertScreenshotAndThumbnailStored($interval, 1280, 720);
    }

    public function test_put_screenshot_stores_screenshot_and_thumbnail(): void
    {
        $interval = IntervalFactory::forUser($this->admin)->forTask($this->task)->create();

        $response = $this->actingAs($this->admin)->post(
            "time-intervals/$interval->id/screenshot",
            ['screenshot' => UploadedFile::fake()->image('screenshot.png', 800, 600)],
            ['Accept' => 'application/json'],
        );

        $response->assertStatus(self::HTTP_NO_CONTENT);

        $this->assertScreenshotAndThumbnailStored($interval, 800, 600);
    }

    private function assertScreenshotAndThumbnailStored(TimeInterval $interval, int $width, int $height): void
    {
        $screenshotPath = $this->screenshotService->getScreenshotPath($interval);
        $thumbPath = $this->screenshotService->getThumbPath($interval);

        Storage::assertExists($screenshotPath);
        Storage::assertExists($thumbPath);

        $screenshot = getimagesize(Storage::path($screenshotPath));
        $this->assertSame([$width, $height, IMAGETYPE_JPEG], array_slice($screenshot, 0, 3));

        $thumb = getimagesize(Storage::path($thumbPath));
        $this->assertSame(
            [self::THUMB_WIDTH, (int)round($height * self::THUMB_WIDTH / $width), IMAGETYPE_JPEG],
            array_slice($thumb, 0, 3)
        );
    }
}
