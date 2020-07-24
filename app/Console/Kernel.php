<?php

namespace App\Console;

use App\Console\Commands\DemoReset;
use App\Console\Commands\EmulateWork;
use App\Console\Commands\PlanWork;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use Laravel\Telescope\Console\PruneCommand;

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
     *
     * @param  Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(EmulateWork::class)
            ->everyFiveMinutes()
            ->environments(['staging', 'demo'])
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command(DemoReset::class)->cron('0 */3 * * *')->environments(['demo']);

        $schedule->command(PlanWork::class)->daily()->environments(['staging']);
        $schedule->command(DemoReset::class)->weeklyOn(1, '1:00')->environments(['staging']);

        $schedule->command(PruneCommand::class)->daily()->environments(['staging', 'local']);
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
