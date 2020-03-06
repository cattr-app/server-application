<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

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

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('redmine-synchronize:tasks')->everyMinute();
        $schedule->command('redmine-synchronize:projects')->everyFiveMinutes();
        $schedule->command('redmine-synchronize:users')->everyFiveMinutes();
        $schedule->command('redmine-synchronize:time')->everyFiveMinutes();
        $schedule->command('redmine-synchronize:priorities')->everyFifteenMinutes();
        $schedule->command('redmine-synchronize:statuses')->everyFifteenMinutes();
        $schedule->command('gitlab:sync')->everyMinute();
        $schedule->command('gitlab:time:sync')->everyFiveMinutes();
    }

    /**
     * Register the Closure based commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
