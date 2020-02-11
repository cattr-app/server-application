<?php

namespace Modules\RedmineIntegration\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    const COMMAND_LIST = [
        'users' => '*/15 * * * *',
        'statuses' => '*/15 * * * *',
        'priorities' => '*/15 * * * *',
        'projects' => '*/5 * * * *',
        'tasks' => '* * * * *',
        'time' => '*/5 * * * *'
    ];
    const COMMAND_PREFIX = 'redmine-synchronize';
    /**
     * @var Schedule
     */
    protected $schedule;
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

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
        $this->schedule = $this->app->make(Schedule::class);
        $this->app->booted(function () {
            foreach (self::COMMAND_LIST as $command => $cron) {
                $this->registerScheduleCommand(self::COMMAND_PREFIX.':'.$command, $cron);
            }
        });
    }

    /**
     * @param  string  $command
     * @param  string  $cron
     *
     * @return $this
     */
    protected function registerScheduleCommand(string $command, string $cron)
    {
        $this->schedule->command($command)->cron($cron)->withoutOverlapping();
        return $this;
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

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
