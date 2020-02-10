<?php

namespace Modules\GitlabIntegration\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    /**
     * ScheduleServiceProvider constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        parent::__construct($app);
    }


    public function boot()
    {
        $schedule = $this->app->make(Schedule::class);
        $schedule->command('gitlab:sync')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('gitlab:time:sync')->everyMinute()->withoutOverlapping();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
