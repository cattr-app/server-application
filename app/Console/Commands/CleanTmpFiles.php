<?php

namespace App\Console\Commands;

use App\Enums\OfflineScreenshotUploadStatus;
use App\Models\OfflineScreenshotUpload;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use SplFileInfo;
use Storage;
use Throwable;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Removes leftovers from storage/app/tmp of the local disk: uploads that older versions never deleted and
 * directories of offline screenshot uploads interrupted before their cleanup.
 *
 * Only direct entries of that directory are checked, and only those not modified for --hours, so files of
 * requests that are still running are kept. Symbolic links are removed, never followed.
 *
 * Also removes the stored archives of offline screenshot uploads whose job was lost (or never finished) from the
 * default disk, which may be a shared volume or S3: the upload is marked as failed. Finished uploads (with
 * their per-screenshot results) are deleted after UPLOAD_RETENTION_DAYS.
 */
#[AsCommand(name: 'cattr:tmp:clean')]
class CleanTmpFiles extends Command
{
    public const DEFAULT_HOURS = 24;

    /** Days the result of a finished offline screenshot upload stays available */
    public const UPLOAD_RETENTION_DAYS = 7;

    protected $signature = 'cattr:tmp:clean
        {--hours=' . self::DEFAULT_HOURS . ' : Delete entries not modified for at least this many hours}';

    protected $description = 'Delete old temporary files and directories from storage/app/tmp of the local disk '
        . 'and stored archives of lost offline screenshot uploads';

    public function handle(Filesystem $files): int
    {
        $hours = filter_var($this->option('hours'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($hours === false) {
            $this->error('The --hours option must be a positive integer.');

            return self::INVALID;
        }

        $threshold = now()->subHours($hours)->getTimestamp();

        $tmpFailed = $this->cleanTmp($files, $hours, $threshold);
        $archivesFailed = $this->cleanOfflineScreenshotArchives($hours, $threshold);
        $this->pruneFinishedOfflineScreenshotUploads();

        return $tmpFailed + $archivesFailed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return int Number of entries that could not be deleted
     */
    private function cleanTmp(Filesystem $files, int $hours, int $threshold): int
    {
        $tmpPath = Storage::disk('local')->path('tmp');
        if (!is_dir($tmpPath)) {
            $this->info('Nothing to clean: ' . $tmpPath . ' does not exist.');

            return 0;
        }

        $deleted = 0;
        $failed = 0;

        /** @var SplFileInfo $entry */
        foreach (new \FilesystemIterator($tmpPath, \FilesystemIterator::SKIP_DOTS) as $entry) {
            // lstat(): the age of a symbolic link itself, without following it.
            $stat = @lstat($entry->getPathname());
            if ($entry->getFilename() === '.gitignore' || $stat === false || $stat['mtime'] > $threshold) {
                continue;
            }

            $ok = $entry->isDir() && !$entry->isLink()
                ? $files->deleteDirectory($entry->getPathname())
                : $files->delete($entry->getPathname());

            $ok ? $deleted++ : $failed++;
        }

        $this->info("Deleted $deleted entries older than $hours h from $tmpPath.");
        if ($failed > 0) {
            $this->warn("Could not delete $failed entries.");
        }

        return $failed;
    }

    /**
     * @return int Number of archives that could not be deleted
     */
    private function cleanOfflineScreenshotArchives(int $hours, int $threshold): int
    {
        $deleted = 0;
        $failed = 0;
        $before = now()->setTimestamp($threshold);

        // Both queries below change the rows they select, so they are read by id: a paging by offset skips rows.

        // Uploads whose job never finished (lost, or waiting longer than the threshold) can never be completed
        OfflineScreenshotUpload::query()
            ->whereIn('status', [OfflineScreenshotUploadStatus::PENDING, OfflineScreenshotUploadStatus::PROCESSING])
            ->where('updated_at', '<', $before)
            ->eachById(function (OfflineScreenshotUpload $upload) use (&$deleted, &$failed, $before) {
                // The row was loaded earlier: a job may have started since, then it is not stale any more
                if ($upload->markFailedIfStale($before)) {
                    $upload->path === null ? $deleted++ : $failed++;
                }
            });

        // Finished uploads whose archive could not be deleted at that time
        OfflineScreenshotUpload::query()
            ->whereNotNull('path')
            ->where('updated_at', '<', $before)
            ->eachById(function (OfflineScreenshotUpload $upload) use (&$deleted, &$failed) {
                try {
                    $upload->deleteArchive();
                    $deleted++;
                } catch (Throwable $e) {
                    report($e);
                    $failed++;
                }
            });

        // Archives without an upload row: an archive that a row still refers to belongs to its job (or to the
        // retry above), however old the file is
        $diskName = config('filesystems.default');
        $disk = Storage::disk($diskName);
        try {
            foreach ($disk->files(OfflineScreenshotUpload::ARCHIVE_DIRECTORY) as $path) {
                if ($disk->lastModified($path) > $threshold
                    || OfflineScreenshotUpload::query()->where('disk', $diskName)->where('path', $path)->exists()
                ) {
                    continue;
                }

                $disk->delete($path) ? $deleted++ : $failed++;
            }
        } catch (Throwable $e) {
            report($e);
            $failed++;
        }

        $this->info("Deleted $deleted offline screenshot archives older than $hours h.");
        if ($failed > 0) {
            $this->warn("Could not delete $failed offline screenshot archives.");
        }

        return $failed;
    }

    /**
     * Deletes the finished uploads and, by cascade, their results. An upload whose archive could not be deleted
     * yet is kept, so that the archive can still be found and removed.
     */
    private function pruneFinishedOfflineScreenshotUploads(): void
    {
        $deleted = OfflineScreenshotUpload::query()
            ->whereIn('status', [OfflineScreenshotUploadStatus::COMPLETED, OfflineScreenshotUploadStatus::FAILED])
            ->whereNull('path')
            ->where('finished_at', '<', now()->subDays(self::UPLOAD_RETENTION_DAYS))
            ->delete();

        $this->info("Deleted $deleted offline screenshot uploads finished more than "
            . self::UPLOAD_RETENTION_DAYS . ' days ago.');
    }
}
