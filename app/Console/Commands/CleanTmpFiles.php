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
 * default disk, which may be a shared volume or S3: the upload is marked as failed.
 */
#[AsCommand(name: 'cattr:tmp:clean')]
class CleanTmpFiles extends Command
{
    public const DEFAULT_HOURS = 24;

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

        // Uploads whose job never finished (lost, or waiting longer than the threshold) can never be completed
        OfflineScreenshotUpload::query()
            ->whereIn('status', [OfflineScreenshotUploadStatus::PENDING, OfflineScreenshotUploadStatus::PROCESSING])
            ->where('updated_at', '<', $before)
            ->each(function (OfflineScreenshotUpload $upload) use (&$deleted, &$failed) {
                $upload->markFailed();
                $upload->path === null ? $deleted++ : $failed++;
            });

        // Finished uploads whose archive could not be deleted at that time
        OfflineScreenshotUpload::query()
            ->whereNotNull('path')
            ->where('updated_at', '<', $before)
            ->each(function (OfflineScreenshotUpload $upload) use (&$deleted, &$failed) {
                try {
                    $upload->deleteArchive();
                    $deleted++;
                } catch (Throwable $e) {
                    report($e);
                    $failed++;
                }
            });

        // Archives without an upload row
        $disk = Storage::disk(config('filesystems.default'));
        try {
            foreach ($disk->files(OfflineScreenshotUpload::ARCHIVE_DIRECTORY) as $path) {
                if ($disk->lastModified($path) > $threshold) {
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
}
