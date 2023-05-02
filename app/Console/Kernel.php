<?php

namespace App\Console;

use App\Console\Commands\RotateScreenshots;
use App\Jobs\ClearExpiredApps;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Sentry\State\Scope;
use Settings;
use function Sentry\configureScope;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    public final function bootstrap(): void
    {
        parent::bootstrap();
    }

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     * @throws Exception
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(RotateScreenshots::class)->weekly()->when(Settings::scope('core')->get('auto_thinning'));

        $schedule->job(new ClearExpiredApps)->daily();
    }

    /**
     * Register the Closure based commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
