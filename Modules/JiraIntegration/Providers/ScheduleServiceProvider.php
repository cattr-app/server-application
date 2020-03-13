<?php

namespace Modules\JiraIntegration\Providers;

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
        $schedule->command('jira:sync-tasks')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('jira:sync-time')->everyMinute()->withoutOverlapping();
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
