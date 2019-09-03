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
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('gitlab:sync')->everyFiveMinutes()->withoutOverlapping();
        });
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
