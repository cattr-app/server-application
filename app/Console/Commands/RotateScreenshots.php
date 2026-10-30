<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\StorageCleaner;
use App\Helpers\StorageCleanupStatus;
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
    protected $signature = 'cattr:screenshots:rotate';

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

        $lock = StorageCleanupStatus::runLock();

        if (!$lock->get()) {
            // The run in progress records its own outcome
            $this->info('Another thinning is in progress');
            return;
        }

        try {
            if (!StorageCleaner::needThinning()) {
                StorageCleanupStatus::markSkipped();
                $this->info('Time for thinning hasn\'t come');
                return;
            }

            StorageCleanupStatus::markRunning();

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
}
