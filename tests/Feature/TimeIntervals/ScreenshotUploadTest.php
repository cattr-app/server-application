<?php

namespace Tests\Feature\TimeIntervals;

use App\Contracts\ScreenshotService;
use App\Jobs\GenerateScreenshotThumbnail;
use App\Models\CronTaskWorkers;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use App\Jobs\AssignAppsToTimeInterval;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use RuntimeException;
use Throwable;
use Tests\TestCase;
use CatEvent;

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

    public function test_create_with_unwritable_screenshot_creates_nothing_and_can_be_retried(): void
    {
        Queue::fake();
        $intervalData = $this->intervalDataWithScreenshot();
        // The disks do not throw: a failed write is only reported by the return value
        $disk = \Mockery::mock(Storage::disk('local'))->makePartial();
        $disk->shouldReceive('put')->andReturn(false);
        Storage::set('local', $disk);

        $this->actingAs($this->admin)->post(self::CREATE_URI, $intervalData, ['Accept' => 'application/json'])
            ->assertStatus(self::HTTP_INTERNAL_SERVER_ERROR);

        $this->assertSame(0, TimeInterval::whereUserId($this->admin->id)->count());
        $this->assertSame(0, CronTaskWorkers::whereUserId($this->admin->id)->count());
        Queue::assertNotPushed(GenerateScreenshotThumbnail::class);

        Storage::fake();
        $this->actingAs($this->admin)->post(self::CREATE_URI, $intervalData, ['Accept' => 'application/json'])
            ->assertOk();

        $interval = TimeInterval::whereUserId($this->admin->id)->sole();
        Storage::assertExists($this->screenshotService->getScreenshotPath($interval));
        Queue::assertPushed(GenerateScreenshotThumbnail::class);
    }

    public function test_rolled_back_create_removes_the_written_screenshot(): void
    {
        // The real (sync) queue: Queue::fake() ignores afterCommit(), the job must not run after the rollback
        $intervalData = $this->intervalDataWithScreenshot();
        $written = [];
        // Runs after the screenshot listener of the controller
        CatEvent::listen('event.after.action.*', static function () use (&$written) {
            $written = Storage::allFiles();
            throw new RuntimeException('a later step failed');
        });

        $this->actingAs($this->admin)->post(self::CREATE_URI, $intervalData, ['Accept' => 'application/json'])
            ->assertStatus(self::HTTP_INTERNAL_SERVER_ERROR);

        $this->assertNotEmpty($written, 'The screenshot was written before the failure');
        $this->assertSame(0, TimeInterval::whereUserId($this->admin->id)->count());
        $this->assertSame(0, CronTaskWorkers::whereUserId($this->admin->id)->count());
        $this->assertSame([], Storage::allFiles(), 'Neither the screenshot nor a thumbnail of the rolled back interval is left');
    }

    public function test_failed_thumbnail_dispatch_after_commit_is_reported_and_does_not_fail_the_request(): void
    {
        Exceptions::fake();
        $intervalData = $this->intervalDataWithScreenshot();
        // The sync queue builds the thumbnail inside the request, after the commit
        $real = Storage::disk('local');
        $disk = \Mockery::mock(Storage::disk('local'))->makePartial();
        $disk->shouldReceive('put')->with(\Mockery::pattern('#/' . ScreenshotService::THUMBS_FOLDER . '#'), \Mockery::any())->andReturn(false);
        $disk->shouldReceive('put')->andReturnUsing(fn (...$args) => $real->put(...$args));
        Storage::set('local', $disk);

        $this->actingAs($this->admin)->post(self::CREATE_URI, $intervalData, ['Accept' => 'application/json'])
            ->assertOk();

        $interval = TimeInterval::whereUserId($this->admin->id)->sole();
        Exceptions::assertReported(
            fn (RuntimeException $e) => str_contains($e->getMessage(), 'Cannot write the screenshot thumbnail'),
        );
        Storage::assertExists($this->screenshotService->getScreenshotPath($interval));
    }

    public function test_failed_apps_dispatch_after_commit_is_reported_and_does_not_fail_the_request(): void
    {
        Exceptions::fake();
        $this->admin->update(['web_and_app_monitoring' => true]);
        $intervalData = $this->intervalDataWithScreenshot();
        unset($intervalData['screenshot']);
        $this->mock(Dispatcher::class)
            ->shouldReceive('dispatch')
            ->with(\Mockery::type(AssignAppsToTimeInterval::class))
            ->andThrow(new RuntimeException('queue is down'));

        $this->actingAs($this->admin)->post(self::CREATE_URI, $intervalData, ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertSame(1, TimeInterval::whereUserId($this->admin->id)->count());
        Exceptions::assertReported(fn (RuntimeException $e) => $e->getMessage() === 'queue is down');
    }

    public function test_failing_reporter_does_not_fail_the_request_after_a_failed_thumbnail_dispatch(): void
    {
        $intervalData = $this->intervalDataWithScreenshot();
        $real = Storage::disk('local');
        $disk = \Mockery::mock(Storage::disk('local'))->makePartial();
        $disk->shouldReceive('put')->with(\Mockery::pattern('#/' . ScreenshotService::THUMBS_FOLDER . '#'), \Mockery::any())->andReturn(false);
        $disk->shouldReceive('put')->andReturnUsing(fn (...$args) => $real->put(...$args));
        Storage::set('local', $disk);
        $this->failReporting();

        $this->actingAs($this->admin)->post(self::CREATE_URI, $intervalData, ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertSame(1, TimeInterval::whereUserId($this->admin->id)->count());
    }

    public function test_failing_reporter_does_not_fail_the_request_after_a_failed_apps_dispatch(): void
    {
        $this->admin->update(['web_and_app_monitoring' => true]);
        $intervalData = $this->intervalDataWithScreenshot();
        unset($intervalData['screenshot']);
        $this->mock(Dispatcher::class)
            ->shouldReceive('dispatch')
            ->with(\Mockery::type(AssignAppsToTimeInterval::class))
            ->andThrow(new RuntimeException('queue is down'));
        $this->failReporting();

        $this->actingAs($this->admin)->post(self::CREATE_URI, $intervalData, ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertSame(1, TimeInterval::whereUserId($this->admin->id)->count());
    }

    public function test_failing_cleanup_after_a_rollback_does_not_replace_the_original_error(): void
    {
        $intervalData = $this->intervalDataWithScreenshot();
        $real = Storage::disk('local');
        $disk = \Mockery::mock($real)->makePartial();
        $disk->shouldReceive('put')->andReturnUsing(fn (...$args) => $real->put(...$args));
        $disk->shouldReceive('delete')->andThrow(new RuntimeException('disk is gone'));
        Storage::set('local', $disk);
        CatEvent::listen('event.after.action.*', static function () {
            throw new RuntimeException('a later step failed');
        });
        // Only the cleanup failure cannot be reported; the original error is reported as usual
        $handler = \Mockery::mock($this->app->make(ExceptionHandler::class))->makePartial();
        $handler->shouldReceive('report')
            ->with(\Mockery::on(static fn (Throwable $e) => $e->getMessage() === 'disk is gone'))
            ->andThrow(new RuntimeException('log is not writable'));
        $this->app->instance(ExceptionHandler::class, $handler);

        $response = $this->actingAs($this->admin)
            ->post(self::CREATE_URI, $intervalData, ['Accept' => 'application/json'])
            ->assertStatus(self::HTTP_INTERNAL_SERVER_ERROR);

        $this->assertSame('a later step failed', $response->exception?->getMessage());
        $this->assertSame(0, TimeInterval::whereUserId($this->admin->id)->count());
    }

    /** Reporting itself throws, e.g. the log file is not writable */
    private function failReporting(): void
    {
        $handler = \Mockery::mock($this->app->make(ExceptionHandler::class))->makePartial();
        $handler->shouldReceive('report')->andThrow(new RuntimeException('log is not writable'));
        $this->app->instance(ExceptionHandler::class, $handler);
    }

    private function intervalDataWithScreenshot(): array
    {
        $intervalData = IntervalFactory::createRandomModelData();
        $intervalData['task_id'] = $this->task->id;
        $intervalData['user_id'] = $this->admin->id;

        return $intervalData + ['screenshot' => UploadedFile::fake()->image('screenshot.png', 640, 480)];
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
