<?php

namespace Modules\JiraIntegration\Providers;

use App\EventFilter\Facades\Filter;
use Illuminate\Support\ServiceProvider;
use Modules\JiraIntegration\Console\SyncTasks;

class JiraIntegrationServiceProvider extends ServiceProvider
{
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

        Filter::listen('answer.success.item.create.timeinterval', static function ($data) {
            $timeInterval = $data['interval'];



            return $data;
        });
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
            module_path('JiraIntegration', 'Config/config.php') => config_path('jiraintegration.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path('JiraIntegration', 'Config/config.php'), 'jiraintegration'
        );
    }

    /**
     * Register command
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands([
            SyncTasks::class,
        ]);
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
