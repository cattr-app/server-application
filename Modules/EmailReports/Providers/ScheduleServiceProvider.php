<?php

namespace Modules\EmailReports\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

/**
 * Class ScheduleServiceProvider
 * @package Modules\EmailReports\Providers
 */
class ScheduleServiceProvider extends ServiceProvider
{
    const COMMAND_PREFIX = 'email-reports';
    const COMMAND_SEND = 'send';
    const SEPARATOR = ':';

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
        $this->schedule->command(self::COMMAND_PREFIX . self::SEPARATOR . self::COMMAND_SEND)->dailyAt('12:00');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
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
