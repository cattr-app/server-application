<?php

namespace Tests\Feature\Storage;

use App\Enums\OfflineScreenshotUploadStatus;
use App\Models\OfflineScreenshotUpload;
use Illuminate\Support\Facades\Storage;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CleanTmpFilesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_deletes_only_old_entries_of_the_tmp_directory(): void
    {
        $disk = Storage::disk('local');

        $disk->put('tmp/old-upload.png', 'old');
        $disk->put('tmp/new-upload.png', 'new');
        $disk->put('tmp/1234-5678/7_old-screenshot', 'old');
        $disk->put('tmp/9876-5432/7_new-screenshot', 'new');
        $disk->put('screenshots/old.jpg', 'outside tmp');
        $disk->put('outside/kept.txt', 'linked from tmp');
        symlink($disk->path('outside'), $disk->path('tmp/1234-5678/link-to-outside'));

        $this->age('tmp/old-upload.png', 25);
        $this->age('tmp/1234-5678', 25);
        $this->age('screenshots/old.jpg', 25);

        $this->artisan('cattr:tmp:clean')
            ->expectsOutputToContain('Deleted 2 entries older than 24 h')
            ->assertSuccessful();

        $this->assertFalse($disk->exists('tmp/old-upload.png'));
        $this->assertFalse(is_dir($disk->path('tmp/1234-5678')));
        $this->assertTrue($disk->exists('tmp/new-upload.png'));
        $this->assertTrue($disk->exists('tmp/9876-5432/7_new-screenshot'));
        $this->assertTrue($disk->exists('screenshots/old.jpg'));
        $this->assertTrue($disk->exists('outside/kept.txt'), 'A symbolic link inside tmp must not be followed');
    }

    public function test_hours_option_sets_the_age_threshold(): void
    {
        $disk = Storage::disk('local');
        $disk->put('tmp/upload.png', 'x');
        $this->age('tmp/upload.png', 25);

        $this->artisan('cattr:tmp:clean', ['--hours' => 48])->assertSuccessful();
        $this->assertTrue($disk->exists('tmp/upload.png'));

        $this->artisan('cattr:tmp:clean', ['--hours' => 2])->assertSuccessful();
        $this->assertFalse($disk->exists('tmp/upload.png'));
    }

    public function test_rejects_an_invalid_hours_option(): void
    {
        $disk = Storage::disk('local');
        $disk->put('tmp/upload.png', 'x');
        $this->age('tmp/upload.png', 25);

        $this->artisan('cattr:tmp:clean', ['--hours' => 0])->assertFailed();
        $this->artisan('cattr:tmp:clean', ['--hours' => 'abc'])->assertFailed();

        $this->assertTrue($disk->exists('tmp/upload.png'));
    }

    public function test_succeeds_without_a_tmp_directory(): void
    {
        $this->artisan('cattr:tmp:clean')->expectsOutputToContain('Nothing to clean')->assertSuccessful();
    }

    private function age(string $path, int $hours): void
    {
        $time = now()->subHours($hours)->getTimestamp();
        touch(Storage::disk('local')->path($path), $time, $time);
    }

    public function test_removes_archives_of_lost_offline_screenshot_uploads(): void
    {
        $disk = Storage::disk('local');
        $user = UserFactory::create();
        $make = function (string $name, OfflineScreenshotUploadStatus $status, int $ageHours) use ($disk, $user) {
            $path = "offline-screenshots/$name.cattr";
            $disk->put($path, 'archive');
            $upload = OfflineScreenshotUpload::create([
                'user_id' => $user->id,
                'status' => $status,
                'disk' => 'local',
                'path' => $path,
                'total' => 1,
            ]);
            OfflineScreenshotUpload::whereKey($upload->id)->update(['updated_at' => now()->subHours($ageHours)]);

            return $upload;
        };

        $lost = $make('lost', OfflineScreenshotUploadStatus::PENDING, 25);
        $stuck = $make('stuck', OfflineScreenshotUploadStatus::PROCESSING, 25);
        $leftover = $make('leftover', OfflineScreenshotUploadStatus::COMPLETED, 25);
        $running = $make('running', OfflineScreenshotUploadStatus::PROCESSING, 1);
        $queued = $make('queued', OfflineScreenshotUploadStatus::PENDING, 1);

        $this->artisan('cattr:tmp:clean')->assertSuccessful();

        foreach (['lost', 'stuck', 'leftover'] as $name) {
            $this->assertFalse($disk->exists("offline-screenshots/$name.cattr"), "$name must be deleted");
        }
        $this->assertTrue($disk->exists('offline-screenshots/running.cattr'));
        $this->assertTrue($disk->exists('offline-screenshots/queued.cattr'));

        $this->assertSame(OfflineScreenshotUploadStatus::FAILED, $lost->refresh()->status);
        $this->assertSame(OfflineScreenshotUploadStatus::FAILED, $stuck->refresh()->status);
        $this->assertNull($lost->path);
        $this->assertSame(OfflineScreenshotUploadStatus::COMPLETED, $leftover->refresh()->status);
        $this->assertNull($leftover->path);
        $this->assertSame(OfflineScreenshotUploadStatus::PROCESSING, $running->refresh()->status);
        $this->assertNotNull($running->path);
        $this->assertSame(OfflineScreenshotUploadStatus::PENDING, $queued->refresh()->status);
    }

    public function test_cleanup_does_not_fail_an_upload_that_a_job_has_started_since_it_was_loaded(): void
    {
        $disk = Storage::disk('local');
        $upload = OfflineScreenshotUpload::create([
            'user_id' => UserFactory::create()->id,
            'status' => OfflineScreenshotUploadStatus::PENDING,
            'disk' => 'local',
            'path' => 'offline-screenshots/raced.cattr',
            'total' => 1,
        ]);
        $disk->put($upload->path, 'archive');
        OfflineScreenshotUpload::whereKey($upload->id)->update(['updated_at' => now()->subHours(25)]);

        // The cleanup has loaded the stale row; then the worker takes it
        $loaded = OfflineScreenshotUpload::findOrFail($upload->id);
        $this->assertTrue($upload->claimForProcessing());

        $this->assertFalse($loaded->markFailedIfStale(now()->subHours(24)));

        $this->assertTrue($disk->exists('offline-screenshots/raced.cattr'));
        $this->assertSame(OfflineScreenshotUploadStatus::PROCESSING, $upload->refresh()->status);
        $this->assertNull($upload->finished_at);
    }

    public function test_job_does_not_overwrite_an_upload_that_cleanup_has_failed(): void
    {
        $upload = OfflineScreenshotUpload::create([
            'user_id' => UserFactory::create()->id,
            'status' => OfflineScreenshotUploadStatus::PENDING,
            'disk' => 'local',
            'path' => 'offline-screenshots/raced.cattr',
            'total' => 1,
        ]);
        OfflineScreenshotUpload::whereKey($upload->id)->update(['updated_at' => now()->subHours(25)]);

        // The job has loaded the row as pending; then the cleanup fails it
        $loaded = OfflineScreenshotUpload::findOrFail($upload->id);
        $this->assertTrue($upload->markFailedIfStale(now()->subHours(24)));

        $this->assertFalse($loaded->claimForProcessing());
        $this->assertSame(OfflineScreenshotUploadStatus::FAILED, $upload->refresh()->status);
        $this->assertNull($upload->path);
    }

    public function test_removes_old_archives_without_an_upload_from_the_default_disk(): void
    {
        config(['filesystems.default' => 's3']);
        $disk = Storage::fake('s3');
        $disk->put('offline-screenshots/orphan-old.cattr', 'x');
        $disk->put('offline-screenshots/orphan-new.cattr', 'x');
        $disk->put('screenshots/old.jpg', 'outside');
        touch($disk->path('offline-screenshots/orphan-old.cattr'), now()->subHours(25)->getTimestamp());
        touch($disk->path('screenshots/old.jpg'), now()->subHours(25)->getTimestamp());

        $this->artisan('cattr:tmp:clean')
            ->expectsOutputToContain('Deleted 1 offline screenshot archives older than 24 h')
            ->assertSuccessful();

        $this->assertFalse($disk->exists('offline-screenshots/orphan-old.cattr'));
        $this->assertTrue($disk->exists('offline-screenshots/orphan-new.cattr'));
        $this->assertTrue($disk->exists('screenshots/old.jpg'));
    }

    public function test_keeps_an_old_archive_that_an_upload_still_refers_to(): void
    {
        $disk = Storage::disk('local');
        $user = UserFactory::create();
        $disk->put('offline-screenshots/referenced.cattr', 'x');
        $disk->put('offline-screenshots/orphan.cattr', 'x');
        foreach (['referenced', 'orphan'] as $name) {
            touch($disk->path("offline-screenshots/$name.cattr"), now()->subHours(25)->getTimestamp());
        }
        $upload = OfflineScreenshotUpload::create([
            'user_id' => $user->id,
            'status' => OfflineScreenshotUploadStatus::PROCESSING,
            'disk' => 'local',
            'path' => 'offline-screenshots/referenced.cattr',
            'total' => 1,
        ]);

        $this->artisan('cattr:tmp:clean')->assertSuccessful();

        $this->assertTrue($disk->exists('offline-screenshots/referenced.cattr'));
        $this->assertFalse($disk->exists('offline-screenshots/orphan.cattr'));
        $this->assertSame(OfflineScreenshotUploadStatus::PROCESSING, $upload->refresh()->status);
        $this->assertNotNull($upload->path);
    }

    public function test_processes_every_upload_when_there_are_more_than_one_chunk(): void
    {
        $user = UserFactory::create();
        $old = now()->subHours(25);
        $rows = [];
        // 1001 rows: more than one chunk of eachById(); marking an upload failed removes it from the selection,
        // so reading by offset would skip the rows that move into the first page
        for ($i = 1; $i <= 1001; $i++) {
            $rows[] = [
                'user_id' => $user->id,
                'status' => OfflineScreenshotUploadStatus::PENDING->value,
                'disk' => 'local',
                'path' => "offline-screenshots/$i.cattr",
                'total' => 1,
                'created_at' => $old,
                'updated_at' => $old,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            OfflineScreenshotUpload::query()->insert($chunk);
        }

        $this->artisan('cattr:tmp:clean')
            ->expectsOutputToContain('Deleted 1001 offline screenshot archives')
            ->assertSuccessful();

        $this->assertSame(1001, OfflineScreenshotUpload::where('status', OfflineScreenshotUploadStatus::FAILED)->count());
        $this->assertSame(1001, OfflineScreenshotUpload::query()->whereNull('path')->count());
    }

    public function test_deletes_finished_offline_screenshot_uploads_after_seven_days(): void
    {
        $user = UserFactory::create();
        $make = function (OfflineScreenshotUploadStatus $status, int $finishedDaysAgo, ?string $path = null) use ($user) {
            return OfflineScreenshotUpload::create([
                'user_id' => $user->id,
                'status' => $status,
                'disk' => 'local',
                'path' => $path,
                'total' => 1,
                'finished_at' => now()->subDays($finishedDaysAgo)->subHour(),
            ]);
        };

        $oldCompleted = $make(OfflineScreenshotUploadStatus::COMPLETED, 7);
        $oldFailed = $make(OfflineScreenshotUploadStatus::FAILED, 8);
        $recent = $make(OfflineScreenshotUploadStatus::COMPLETED, 6);
        $withArchive = $make(OfflineScreenshotUploadStatus::COMPLETED, 7, 'offline-screenshots/kept.cattr');
        $unfinished = OfflineScreenshotUpload::create([
            'user_id' => $user->id,
            'status' => OfflineScreenshotUploadStatus::PROCESSING,
            'disk' => 'local',
            'path' => 'offline-screenshots/running.cattr',
            'total' => 1,
        ]);
        $screenshotId = (string)\Illuminate\Support\Str::uuid();
        $oldCompleted->items()->create([
            'entry_name' => "{$user->id}_$screenshotId",
            'user_id' => $user->id,
            'screenshot_id' => $screenshotId,
            'success' => true,
            'message' => 'validation.offline-sync.screenshot_added',
        ]);

        // The archives are not on the disk, so only the rows can be pruned; the one with a path is not
        OfflineScreenshotUpload::whereKey([$withArchive->id, $unfinished->id])->update(['updated_at' => now()]);

        $this->artisan('cattr:tmp:clean')
            ->expectsOutputToContain('Deleted 2 offline screenshot uploads finished more than 7 days ago')
            ->assertSuccessful();

        $this->assertModelMissing($oldCompleted);
        $this->assertModelMissing($oldFailed);
        $this->assertModelExists($recent);
        $this->assertModelExists($withArchive);
        $this->assertModelExists($unfinished);
        $this->assertDatabaseCount('offline_screenshot_upload_items', 0);
    }
}
