<?php

namespace Tests\Feature\TimeIntervals;

use App\Contracts\ScreenshotService;
use App\Enums\OfflineScreenshotUploadStatus;
use App\Enums\Role;
use App\Events\OfflineScreenshotsUploadProgress;
use App\Jobs\GenerateScreenshotThumbnail;
use App\Jobs\ProcessOfflineScreenshotsUpload;
use App\Models\OfflineScreenshotUpload;
use App\Models\OfflineScreenshotUploadItem;
use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Offline screenshot archives are stored on the default disk by the request and attached by a queue job; the
 * results are read from offline-sync/uploads/{id}.
 */
class OfflineScreenshotsUploadTest extends TestCase
{
    use UploadsOfflineScreenshots;

    private ScreenshotService $screenshotService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();

        $this->screenshotService = app(ScreenshotService::class);
    }

    public function test_upload_is_accepted_stored_and_queued(): void
    {
        $user = UserFactory::withTokens()->create();
        $interval = $this->createOfflineInterval($user, TaskFactory::forUser($user)->create());

        $response = $this->uploadOfflineArchive($user, $this->entries($user, $interval));

        $response->assertStatus(self::HTTP_ACCEPTED)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.processed', 0)
            ->assertJsonPath('data.results', []);

        $upload = OfflineScreenshotUpload::findOrFail($response->json('data.id'));
        $this->assertSame($user->id, $upload->user_id);
        $this->assertSame('local', $upload->disk);
        Storage::disk('local')->assertExists($upload->path);
        $this->assertSame([$upload->path], $this->storedOfflineArchives());
        Queue::assertPushed(
            ProcessOfflineScreenshotsUpload::class,
            static fn (ProcessOfflineScreenshotsUpload $job) => $job->upload->is($upload),
        );

        // Nothing is attached until the job runs
        $interval->refresh();
        $this->assertNotNull($interval->screenshot_id);
        $this->assertTmpIsEmpty();
    }

    /**
     * Pods and containers of the web and the queue worker share only the default disk: with `s3` the archive is
     * stored there, not on the local disk.
     */
    public function test_archive_is_stored_on_the_default_disk(): void
    {
        config(['filesystems.default' => 's3']);
        Storage::fake('s3');

        $user = UserFactory::withTokens()->create();
        $interval = $this->createOfflineInterval($user, TaskFactory::forUser($user)->create());

        $this->uploadOfflineArchive($user, $this->entries($user, $interval))->assertStatus(self::HTTP_ACCEPTED);

        $upload = OfflineScreenshotUpload::firstOrFail();
        $this->assertSame('s3', $upload->disk);
        Storage::disk('s3')->assertExists($upload->path);
        $this->assertSame([], Storage::disk('local')->allFiles(), 'Nothing must be stored on the local disk');

        $this->runOfflineUploadJob($upload);

        $this->pollOfflineUpload($user, $upload->id)->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.results.0.success', true);
        $interval->refresh();
        $this->assertNull($interval->screenshot_id);
        Storage::disk('s3')->assertExists($this->screenshotService->getScreenshotPath($interval));
        $this->assertNoStoredArchive();
        $this->assertSame([], Storage::disk('local')->allFiles('tmp'));
    }

    public function test_upload_is_pending_before_the_job_runs(): void
    {
        $user = UserFactory::withTokens()->create();
        $interval = $this->createOfflineInterval($user, TaskFactory::forUser($user)->create());
        $this->uploadOfflineArchive($user, $this->entries($user, $interval));
        $upload = OfflineScreenshotUpload::firstOrFail();

        $this->pollOfflineUpload($user, $upload->id)->assertOk()
            ->assertJsonPath('data.id', $upload->id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.processed', 0)
            ->assertJsonPath('data.results', []);
    }

    public function test_job_attaches_the_screenshots_and_deletes_the_archive(): void
    {
        $user = UserFactory::withTokens()->create();
        $task = TaskFactory::forUser($user)->create();
        $attached = $this->createOfflineInterval($user, $task);
        $missingId = (string)Str::uuid();

        $response = $this->uploadAndProcessOfflineArchive($user, $this->entries($user, $attached) + [
            "{$user->id}_$missingId" => $this->image(),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.processed', 2)
            ->assertJsonCount(2, 'data.results');
        $results = collect($response->json('data.results'));
        $this->assertTrue($results->firstWhere('interval.id', $attached->id)['success']);
        $this->assertSame(
            [
                'interval' => null,
                'user_id' => $user->id,
                'screenshot_id' => $missingId,
                'message' => __('validation.offline-sync.cannot_find_interval'),
                'success' => false,
            ],
            $results->firstWhere('screenshot_id', $missingId),
        );

        $attached->refresh();
        $this->assertNull($attached->screenshot_id);
        Storage::assertExists($this->screenshotService->getScreenshotPath($attached));
        Queue::assertPushed(GenerateScreenshotThumbnail::class, 1);

        $upload = OfflineScreenshotUpload::firstOrFail();
        $this->assertNull($upload->path);
        $this->assertNotNull($upload->finished_at);
        $this->assertNoStoredArchive();
        $this->assertTmpIsEmpty();
    }

    /**
     * An attempt that stops after some entries (here: the result of the second one cannot be saved) is continued
     * by the next one: processed entries are not reported as "interval not found" and not attached again.
     */
    public function test_retried_job_continues_after_the_processed_entries(): void
    {
        $user = UserFactory::withTokens()->create();
        $task = TaskFactory::forUser($user)->create();
        $first = $this->createOfflineInterval($user, $task);
        $second = $this->createOfflineInterval($user, $task);
        $entries = $this->entries($user, $first) + $this->entries($user, $second);
        $this->uploadOfflineArchive($user, $entries)->assertStatus(self::HTTP_ACCEPTED);
        $upload = OfflineScreenshotUpload::firstOrFail();

        $interrupted = true;
        $saved = 0;
        OfflineScreenshotUploadItem::creating(static function () use (&$interrupted, &$saved) {
            if ($interrupted && ++$saved === 2) {
                throw new RuntimeException('The worker died');
            }
        });

        try {
            $this->runOfflineUploadJob($upload);
            $this->fail('The first attempt must be interrupted');
        } catch (RuntimeException $e) {
            $this->assertSame('The worker died', $e->getMessage());
        }

        // The entry that was not saved is not half done: its interval still waits for the screenshot
        $this->assertSame(1, $upload->items()->count());
        $this->assertNull($first->refresh()->screenshot_id);
        $this->assertNotNull($second->refresh()->screenshot_id);
        $this->assertSame(OfflineScreenshotUploadStatus::PROCESSING, $upload->refresh()->status);
        $this->assertNotNull($upload->path, 'The archive is needed by the next attempt');

        $interrupted = false;
        $this->runOfflineUploadJob($upload);

        $response = $this->pollOfflineUpload($user, $upload->id)->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.processed', 2)
            ->assertJsonCount(2, 'data.results');
        foreach ($response->json('data.results') as $result) {
            $this->assertTrue($result['success']);
            $this->assertSame(__('validation.offline-sync.screenshot_attached'), $result['message']);
        }
        $this->assertSame(2, OfflineScreenshotUploadItem::where('upload_id', $upload->id)->count());
        $this->assertNull($second->refresh()->screenshot_id);
        Storage::assertExists($this->screenshotService->getScreenshotPath($first));
        Storage::assertExists($this->screenshotService->getScreenshotPath($second));
        $this->assertNoStoredArchive();
        $this->assertTmpIsEmpty();
    }

    public function test_job_run_again_after_completion_does_nothing(): void
    {
        $user = UserFactory::withTokens()->create();
        $interval = $this->createOfflineInterval($user, TaskFactory::forUser($user)->create());
        $this->uploadAndProcessOfflineArchive($user, $this->entries($user, $interval));
        $upload = OfflineScreenshotUpload::firstOrFail();
        Queue::assertPushed(GenerateScreenshotThumbnail::class, 1);

        $this->runOfflineUploadJob($upload);

        $this->assertSame(1, $upload->items()->count());
        Queue::assertPushed(GenerateScreenshotThumbnail::class, 1);
    }

    public function test_failed_job_marks_the_upload_failed_and_deletes_the_archive(): void
    {
        $user = UserFactory::withTokens()->create();
        $interval = $this->createOfflineInterval($user, TaskFactory::forUser($user)->create());
        $this->uploadOfflineArchive($user, $this->entries($user, $interval));
        $upload = OfflineScreenshotUpload::firstOrFail();
        $archivePath = $upload->path;

        // The stored archive is lost: the attempt fails and, being the last one, the upload is failed
        Storage::disk('local')->delete($archivePath);
        try {
            $this->runOfflineUploadJob($upload);
            $this->fail('The attempt must fail without the archive');
        } catch (RuntimeException) {
            (new ProcessOfflineScreenshotsUpload($upload))->failed(new Exception('Final failure'));
        }

        $upload->refresh();
        $this->assertSame(OfflineScreenshotUploadStatus::FAILED, $upload->status);
        $this->assertNull($upload->path);
        $this->assertNotNull($upload->finished_at);
        $this->assertNoStoredArchive();
        $this->pollOfflineUpload($user, $upload->id)->assertOk()->assertJsonPath('data.status', 'failed');
        $this->assertNotNull($interval->refresh()->screenshot_id);
        $this->assertTmpIsEmpty();
    }

    public function test_final_failure_after_partial_progress_deletes_the_archive_and_keeps_the_results(): void
    {
        $user = UserFactory::withTokens()->create();
        $task = TaskFactory::forUser($user)->create();
        $first = $this->createOfflineInterval($user, $task);
        $second = $this->createOfflineInterval($user, $task);
        $this->uploadOfflineArchive($user, $this->entries($user, $first) + $this->entries($user, $second));
        $upload = OfflineScreenshotUpload::firstOrFail();

        $saved = 0;
        OfflineScreenshotUploadItem::creating(static function () use (&$saved) {
            if (++$saved === 2) {
                throw new RuntimeException('The worker died');
            }
        });
        try {
            $this->runOfflineUploadJob($upload);
        } catch (RuntimeException) {
            (new ProcessOfflineScreenshotsUpload($upload))->failed(new Exception('Final failure'));
        }

        $this->pollOfflineUpload($user, $upload->id)->assertOk()
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.processed', 1)
            ->assertJsonPath('data.results.0.success', true);
        $this->assertNoStoredArchive();
        $this->assertTmpIsEmpty();
    }

    public function test_job_limits_come_from_the_configuration(): void
    {
        config([
            'app.offline_screenshots.job_timeout' => 123,
            'app.offline_screenshots.job_tries' => 4,
        ]);
        $user = UserFactory::create();
        $upload = OfflineScreenshotUpload::create([
            'user_id' => $user->id,
            'status' => OfflineScreenshotUploadStatus::PENDING,
            'total' => 0,
        ]);

        $job = new ProcessOfflineScreenshotsUpload($upload);

        $this->assertSame(123, $job->timeout);
        $this->assertSame(4, $job->tries);
        // A timeout fails the job (and so deletes the archive in failed()) only when no attempts remain
        $this->assertFalse(property_exists($job, 'failOnTimeout') && $job->failOnTimeout);
    }

    public function test_progress_is_broadcast_to_the_uploader(): void
    {
        Event::fake([OfflineScreenshotsUploadProgress::class]);

        $user = UserFactory::withTokens()->create();
        $interval = $this->createOfflineInterval($user, TaskFactory::forUser($user)->create());
        $this->uploadAndProcessOfflineArchive($user, $this->entries($user, $interval));
        $upload = OfflineScreenshotUpload::firstOrFail();

        Event::assertDispatched(
            OfflineScreenshotsUploadProgress::class,
            static fn (OfflineScreenshotsUploadProgress $event) => $event->broadcastWith() === [
                    'id' => $upload->id,
                    'status' => 'completed',
                    'total' => 1,
                    'processed' => 1,
                ]
                && $event->broadcastAs() === 'offline-sync.upload'
                && $event->broadcastOn()[0]->name === "private-offline-sync.$user->id",
        );
    }

    public function test_unavailable_broadcasting_does_not_fail_the_job(): void
    {
        Event::listen(OfflineScreenshotsUploadProgress::class, static function () {
            throw new RuntimeException('Reverb is down');
        });

        $user = UserFactory::withTokens()->create();
        $interval = $this->createOfflineInterval($user, TaskFactory::forUser($user)->create());

        $this->uploadAndProcessOfflineArchive($user, $this->entries($user, $interval))
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.results.0.success', true);
    }

    public function test_only_the_uploader_reads_the_upload(): void
    {
        $uploader = UserFactory::withTokens()->create();
        $interval = $this->createOfflineInterval($uploader, TaskFactory::forUser($uploader)->create());
        $this->uploadOfflineArchive($uploader, $this->entries($uploader, $interval));
        $uploadId = OfflineScreenshotUpload::firstOrFail()->id;

        $this->pollOfflineUpload($uploader, $uploadId)->assertOk();

        $others = [
            'another user' => UserFactory::withTokens()->create(),
            'admin' => UserFactory::asAdmin()->withTokens()->create(),
        ];
        foreach ($others as $label => $other) {
            $this->pollOfflineUpload($other, $uploadId)->assertNotFound('page_not_found');
        }

        $this->pollOfflineUpload($uploader, $uploadId + 1000)->assertNotFound('page_not_found');
    }

    public function test_result_stays_but_the_interval_is_hidden_when_the_uploader_can_no_longer_read_it(): void
    {
        $uploader = $this->createOfflineAdmin();
        $owner = UserFactory::create();
        $interval = $this->createOfflineInterval($owner, TaskFactory::forUser($owner)->create());
        $screenshotId = $interval->screenshot_id;

        $this->uploadAndProcessOfflineArchive($uploader, ["{$owner->id}_$screenshotId" => $this->image()])
            ->assertOk()
            ->assertJsonPath('data.results.0.interval.id', $interval->id);

        $uploader->update(['role_id' => Role::USER->value]);

        $this->pollOfflineUpload($uploader, OfflineScreenshotUpload::firstOrFail()->id)
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.processed', 1)
            ->assertJsonPath('data.results.0.interval', null)
            ->assertJsonPath('data.results.0.success', true)
            ->assertJsonPath('data.results.0.screenshot_id', $screenshotId)
            ->assertJsonPath('data.results.0.message', __('validation.offline-sync.screenshot_attached'));
    }

    public function test_unauthenticated_user_cannot_read_an_upload(): void
    {
        $user = UserFactory::create();
        $upload = OfflineScreenshotUpload::create([
            'user_id' => $user->id,
            'status' => OfflineScreenshotUploadStatus::PENDING,
            'total' => 0,
        ]);

        $this->getJson("offline-sync/uploads/$upload->id")->assertUnauthorized();
    }

    public function test_unauthenticated_user_cannot_upload(): void
    {
        $this->post(
            'offline-sync/upload-screenshots',
            ['file' => new UploadedFile($this->createOfflineArchive(['1_x' => 'x']), 'screenshots.cattr', 'application/zip', null, true)],
            ['Accept' => 'application/json'],
        )->assertUnauthorized();

        Queue::assertNotPushed(ProcessOfflineScreenshotsUpload::class);
        $this->assertNoStoredArchive();
    }

    public function test_archive_that_cannot_be_deleted_stays_referenced_by_the_upload(): void
    {
        $user = UserFactory::withTokens()->create();
        $interval = $this->createOfflineInterval($user, TaskFactory::forUser($user)->create());
        $this->uploadOfflineArchive($user, $this->entries($user, $interval))->assertStatus(self::HTTP_ACCEPTED);
        $upload = OfflineScreenshotUpload::firstOrFail();
        $path = $upload->path;
        $this->failDeletingFromTheLocalDisk();

        try {
            $upload->deleteArchive();
            $this->fail('A failed delete must be reported');
        } catch (RuntimeException) {
            // expected
        }

        $upload->refresh();
        $this->assertSame('local', $upload->disk);
        $this->assertSame($path, $upload->path);

        // The job does not fail because of it: the result is complete, the sweep removes the archive later
        $this->runOfflineUploadJob($upload);
        $upload->refresh();
        $this->assertSame(OfflineScreenshotUploadStatus::COMPLETED, $upload->status);
        $this->assertSame($path, $upload->path);

        // A failed upload keeps the reference as well, and cattr:tmp:clean reports what it cannot delete
        $upload->forceFill(['status' => OfflineScreenshotUploadStatus::FAILED])->save();
        $upload->markFailed();
        $this->assertSame($path, $upload->refresh()->path);

        OfflineScreenshotUpload::whereKey($upload->id)->update(['updated_at' => now()->subDays(2)]);
        $this->artisan('cattr:tmp:clean')
            ->expectsOutputToContain('Could not delete 1 offline screenshot archives')
            ->assertFailed();
        $this->assertSame($path, $upload->refresh()->path);
    }

    public function test_archive_is_removed_if_the_job_cannot_be_queued(): void
    {
        $user = UserFactory::withTokens()->create();
        $interval = $this->createOfflineInterval($user, TaskFactory::forUser($user)->create());
        OfflineScreenshotUpload::created(static fn () => throw new RuntimeException('Queue is down'));

        $this->uploadOfflineArchive($user, $this->entries($user, $interval))
            ->assertStatus(self::HTTP_INTERNAL_SERVER_ERROR);

        $this->assertNoStoredArchive();
    }

    /** The disks are configured not to throw: a failed delete is only reported by the return value */
    private function failDeletingFromTheLocalDisk(): void
    {
        $disk = \Mockery::mock(Storage::disk('local'))->makePartial();
        $disk->shouldReceive('delete')->andReturn(false);
        Storage::set('local', $disk);
    }

    /**
     * @return array<string, string> The screenshot of the interval
     */
    private function entries(User $user, \App\Models\TimeInterval $interval): array
    {
        return ["{$user->id}_$interval->screenshot_id" => $this->image()];
    }

    private function image(): string
    {
        return UploadedFile::fake()->image('s.png', 64, 48)->getContent();
    }
}
