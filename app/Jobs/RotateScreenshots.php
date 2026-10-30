<?php

namespace App\Jobs;

use App\Console\Commands\RotateScreenshots as RotateScreenshotsCommand;
use App\Helpers\StorageCleanupStatus;
use Artisan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Screenshot cleanup requested from the About page.
 *
 * Replaces Artisan::queue(): a queued command has neither its own timeout (the worker kills it after 60 s)
 * nor a failed() hook, so a killed run left its status active.
 */
class RotateScreenshots implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * A failed cleanup is reported to the admin, who can request it again.
     */
    public int $tries = 1;

    public bool $failOnTimeout = true;

    public int $timeout;

    public function __construct()
    {
        $this->timeout = config('cleaner.job_timeout');
    }

    public function handle(): void
    {
        Artisan::call(RotateScreenshotsCommand::class);
    }

    /**
     * Also called when the worker kills the job on timeout: the command had no chance to clean up after itself.
     */
    public function failed(?Throwable $exception = null): void
    {
        if (in_array(StorageCleanupStatus::get()['status'], StorageCleanupStatus::ACTIVE, true)) {
            StorageCleanupStatus::markFailed();
            StorageCleanupStatus::runLock()->forceRelease();
        }
    }
}
