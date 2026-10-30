<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Str;
use App\Helpers\StorageCleaner;
use App\Helpers\StorageCleanupStatus;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'cattr:screenshots:rotate')]
class RotateScreenshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:screenshots:rotate
                            {--run= : Id of the run requested from the About page (a new one by default)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate screenshots';

    /**
     * Execute the console command.
     *
     * Every outcome is recorded in StorageCleanupStatus, so the About page can follow a cleanup
     * queued by the web container and run by the queue worker.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        if (!StorageCleaner::isSupported()) {
            StorageCleanupStatus::markSkipped();
            $this->info('Screenshots are on object storage: use bucket lifecycle rules instead of rotation');
            return;
        }

        $requested = (bool)$this->option('run');
        $runId = $this->option('run') ?: (string)Str::uuid();
        $lock = StorageCleanupStatus::runLock($runId);

        if (!$this->acquire($lock, $requested)) {
            if ($requested && StorageCleanupStatus::isCurrent($runId)) {
                // Reported as failed by the job, the admin can request it again
                throw new RuntimeException('The run lock is still held by another thinning');
            }

            // The run in progress records its own outcome
            $this->info('Another thinning is in progress');
            return;
        }

        try {
            if (!StorageCleanupStatus::markRunning($runId, $requested)) {
                // Its job runs on its own and records the outcome
                $this->info('The request has been replaced by a newer one');
                return;
            }

            if (!StorageCleaner::needThinning()) {
                StorageCleanupStatus::markSkipped();
                $this->info('Time for thinning hasn\'t come');
                return;
            }

            $this->info('For thinning available ' . StorageCleaner::countAvailableScreenshots() . ' screenshots');

            $spaceBefore = StorageCleaner::getUsedSpace();

            $this->info('Started thinning...');

            StorageCleaner::thin(heartbeat: StorageCleanupStatus::touch(...));

            StorageCleanupStatus::markCompleted();

            $this->info('Totally freed ' . round(
                ($spaceBefore - StorageCleaner::getUsedSpace()) / 1024 / 1024,
                3
            ) . 'MB');
        } catch (Throwable $e) {
            StorageCleanupStatus::markFailed();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    /**
     * Takes the run lock. A requested run waits for it: the holder can be a replaced request that only checks
     * it is not current and leaves, and giving up then would drop the current request.
     */
    private function acquire(Lock $lock, bool $requested): bool
    {
        if (!$requested) {
            return (bool)$lock->get();
        }

        try {
            return (bool)$lock->block(config('cleaner.lock_wait'));
        } catch (LockTimeoutException) {
            return false;
        }
    }
}
