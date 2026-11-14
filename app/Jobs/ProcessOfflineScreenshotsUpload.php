<?php

namespace App\Jobs;

use App\Contracts\ScreenshotService;
use App\Enums\OfflineScreenshotUploadStatus;
use App\Events\OfflineScreenshotsUploadProgress;
use App\Helpers\OfflineScreenshotsArchive;
use App\Models\OfflineScreenshotUpload;
use App\Models\OfflineScreenshotUploadItem;
use App\Models\TimeInterval;
use App\Models\User;
use App\Scopes\TimeIntervalAccessScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Throwable;

/**
 * Attaches the screenshots of a stored offline screenshot archive to their intervals.
 *
 * The archive is on the default disk, shared with the web containers. The result of every entry is saved together
 * with the change of its interval, so an attempt that was interrupted or timed out is continued by the next one
 * after the entries that are already processed: nothing is reported as "interval not found" or attached twice.
 */
class ProcessOfflineScreenshotsUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Progress is broadcast after this many processed entries */
    private const BROADCAST_EVERY = 10;

    /** Delete the job if the upload no longer exists (e.g. the uploader was deleted). */
    public bool $deleteWhenMissingModels = true;

    /** Seconds the worker lets one attempt run; always below QUEUE_RETRY_AFTER, see config/app.php */
    public int $timeout;

    public int $tries;

    public bool $failOnTimeout = true;

    /** Seconds before the next attempt after an error */
    public int $backoff = 30;

    public function __construct(public OfflineScreenshotUpload $upload)
    {
        $this->timeout = (int)config('app.offline_screenshots.job_timeout');
        $this->tries = (int)config('app.offline_screenshots.job_tries');
    }

    public function handle(ScreenshotService $screenshotService): void
    {
        $upload = $this->upload->fresh();
        if ($upload === null || $upload->status->isFinished()) {
            return;
        }

        if ($upload->path === null) {
            // The archive is gone (e.g. removed as a leftover), nothing can be processed
            $upload->markFailed();
            $this->broadcast($upload, $upload->items()->count());

            return;
        }

        $upload->forceFill(['status' => OfflineScreenshotUploadStatus::PROCESSING])->save();

        $processedNames = $upload->items()->pluck('entry_name')->flip();
        $processed = $processedNames->count();
        $this->broadcast($upload, $processed);

        $uploader = User::query()->findOrFail($upload->user_id);

        $temporaryDirectory = (new TemporaryDirectory())
            ->location(Storage::disk('local')->path('tmp'))->force()->create();

        $archive = null;
        try {
            $archive = OfflineScreenshotsArchive::open($this->downloadArchive($upload, $temporaryDirectory));

            for ($index = 0, $count = $archive->count(); $index < $count; $index++) {
                $name = $archive->entryName($index);
                if ($processedNames->has($name)) {
                    continue;
                }

                $this->processEntry(
                    $upload,
                    $uploader,
                    $archive,
                    $index,
                    $name,
                    $temporaryDirectory->path() . DIRECTORY_SEPARATOR . 'entry',
                    $screenshotService,
                );

                if (++$processed % self::BROADCAST_EVERY === 0) {
                    $this->broadcast($upload, $processed);
                }
            }
        } finally {
            $archive?->close();
            $temporaryDirectory->delete();
        }

        $upload->forceFill([
            'status' => OfflineScreenshotUploadStatus::COMPLETED,
            'finished_at' => now(),
        ])->save();

        try {
            $upload->deleteArchive();
        } catch (Throwable $e) {
            // The result is complete; cattr:tmp:clean removes the archive
            report($e);
        }

        $this->broadcast($upload, $processed);
    }

    /**
     * The last attempt failed: the upload will never be completed, so its archive is not needed any more.
     */
    public function failed(?Throwable $exception = null): void
    {
        Log::error('Job ProcessOfflineScreenshotsUpload failed', [
            'upload_id' => $this->upload->id,
            'exception' => $exception,
        ]);

        $upload = $this->upload->fresh();
        if ($upload === null || $upload->status === OfflineScreenshotUploadStatus::COMPLETED) {
            return;
        }

        $upload->markFailed();
        $this->broadcast($upload, $upload->items()->count());
    }

    /**
     * Copies the archive from the shared disk to the local temporary directory (ZipArchive needs a local file).
     */
    private function downloadArchive(OfflineScreenshotUpload $upload, TemporaryDirectory $directory): string
    {
        $source = Storage::disk($upload->disk)->readStream($upload->path);
        if (!is_resource($source)) {
            throw new RuntimeException("Cannot read the stored archive of the offline screenshot upload $upload->id");
        }

        try {
            $localPath = $directory->path() . DIRECTORY_SEPARATOR . 'archive.cattr';
            $target = fopen($localPath, 'wb');
            if ($target === false) {
                throw new RuntimeException("Cannot create a local copy of the archive of the upload $upload->id");
            }

            try {
                if (stream_copy_to_stream($source, $target) === false) {
                    throw new RuntimeException("Cannot copy the archive of the offline screenshot upload $upload->id");
                }
            } finally {
                fclose($target);
            }
        } finally {
            fclose($source);
        }

        return $localPath;
    }

    private function processEntry(
        OfflineScreenshotUpload $upload,
        User $uploader,
        OfflineScreenshotsArchive $archive,
        int $index,
        string $name,
        string $localPath,
        ScreenshotService $screenshotService,
    ): void {
        $parsed = OfflineScreenshotsArchive::parseEntryName($name);
        if ($parsed === null) {
            // Rejected by the request; kept as a result so that a damaged archive does not stop the others
            $this->saveItem($upload, $name, 0, $name, null, false, 'Wrong screenshot file name');

            return;
        }

        [$userId, $screenshotId] = $parsed;

        // Same rule as for the offline intervals upload: whoever may create the interval may attach its screenshot.
        // The access scope is not applied (it depends on the request): the policy decides, as before.
        $interval = TimeInterval::withoutGlobalScope(TimeIntervalAccessScope::class)
            ->where('user_id', $userId)
            ->where('screenshot_id', $screenshotId)
            ->first();

        // An interval of someone the uploader may not track time for is reported exactly like a missing one
        if ($interval === null || !$uploader->can('create', [TimeInterval::class, $interval->user_id, $interval->task_id, false])) {
            $this->saveItem(
                $upload,
                $name,
                $userId,
                $screenshotId,
                null,
                false,
                'validation.offline-sync.cannot_find_interval',
            );

            return;
        }

        try {
            $archive->extractEntry($index, $localPath);
            $screenshotService->saveScreenshot($localPath, $interval);
        } catch (Throwable $e) {
            Log::error($e);
            $this->saveItem(
                $upload,
                $name,
                $userId,
                $screenshotId,
                $interval->id,
                false,
                'validation.offline-sync.screenshot_not_attached',
            );

            return;
        } finally {
            @unlink($localPath);
        }

        // One transaction: a crash between the two writes must not leave the interval without its result.
        // Before it commits the next attempt processes the entry again and overwrites the same screenshot.
        DB::transaction(function () use ($interval, $upload, $name, $userId, $screenshotId) {
            $interval->screenshot_id = null;
            $interval->save();

            $this->saveItem(
                $upload,
                $name,
                $userId,
                $screenshotId,
                $interval->id,
                true,
                'validation.offline-sync.screenshot_attached',
            );
        });
    }

    private function saveItem(
        OfflineScreenshotUpload $upload,
        string $name,
        int $userId,
        string $screenshotId,
        ?int $intervalId,
        bool $success,
        string $message,
    ): void {
        OfflineScreenshotUploadItem::query()->create([
            'upload_id' => $upload->id,
            'entry_name' => $name,
            'user_id' => $userId,
            'screenshot_id' => $screenshotId,
            'interval_id' => $intervalId,
            'success' => $success,
            'message' => $message,
        ]);
    }

    /**
     * Reverb is optional: the client falls back to polling, so a broadcast problem must not fail the job.
     */
    private function broadcast(OfflineScreenshotUpload $upload, int $processed): void
    {
        try {
            event(OfflineScreenshotsUploadProgress::for($upload, $processed));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
