<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use SplFileInfo;
use Storage;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Removes leftovers from storage/app/tmp of the local disk: uploads that older versions never deleted and
 * directories of offline screenshot uploads interrupted before their cleanup.
 *
 * Only direct entries of that directory are checked, and only those not modified for --hours, so files of
 * requests that are still running are kept. Symbolic links are removed, never followed.
 */
#[AsCommand(name: 'cattr:tmp:clean')]
class CleanTmpFiles extends Command
{
    public const DEFAULT_HOURS = 24;

    protected $signature = 'cattr:tmp:clean
        {--hours=' . self::DEFAULT_HOURS . ' : Delete entries not modified for at least this many hours}';

    protected $description = 'Delete old temporary files and directories from storage/app/tmp of the local disk';

    public function handle(Filesystem $files): int
    {
        $hours = filter_var($this->option('hours'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($hours === false) {
            $this->error('The --hours option must be a positive integer.');

            return self::INVALID;
        }

        $tmpPath = Storage::disk('local')->path('tmp');
        if (!is_dir($tmpPath)) {
            $this->info('Nothing to clean: ' . $tmpPath . ' does not exist.');

            return self::SUCCESS;
        }

        $threshold = now()->subHours($hours)->getTimestamp();
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

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
