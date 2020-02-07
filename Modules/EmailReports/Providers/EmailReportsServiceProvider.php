<?php

namespace Modules\EmailReports\Providers;

use App\EventFilter\Facades\Filter;
use Illuminate\Support\ServiceProvider;
use Modules\EmailReports\Console\EmailReportsSender;

/**
 * Class EmailReportsServiceProvider
 * @package Modules\EmailReports\Providers
 */
class EmailReportsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerCommands();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'emailreports');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('emailreports.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'emailreports'
        );
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

    private function registerCommands()
    {
        $this->commands([
            EmailReportsSender::class,
        ]);
    }
}
