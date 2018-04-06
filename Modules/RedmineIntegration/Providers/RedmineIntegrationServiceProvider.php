<?php

namespace Modules\RedmineIntegration\Providers;

use Illuminate\Database\Eloquent\Factory;
use App\EventFilter\EventServiceProvider as ServiceProvider;
use Modules\RedmineIntegration\Entities\Repositories\ProjectRepository;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Modules\RedmineIntegration\Helpers\TaskIntegrationHelper;
use Modules\RedmineIntegration\Helpers\TimeIntervalIntegrationHelper;

/**
 * Class RedmineIntegrationServiceProvider
 *
 * @package Modules\RedmineIntegration\Providers
 */
class RedmineIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    protected $listen = [
        'answer.success.item.list.allowed' => [
            'Modules\RedmineIntegration\Listeners\RoleObserver@list',
        ]
    ];


    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //Register Helpers and Repositories for DI
        $this->app->singleton(TaskIntegrationHelper::class, function ($app) {
            return new TaskIntegrationHelper();
        });

        $this->app->singleton(TimeIntervalIntegrationHelper::class, function ($app) {
            return new TimeIntervalIntegrationHelper();
        });

        $this->app->singleton(UserRepository::class, function ($app) {
            return new UserRepository();
        });

        $this->app->singleton(ProjectRepository::class, function ($app) {
            return new ProjectRepository();
        });
    }

    protected function registerCommands()
    {
        //Register synchronize redmine tasks command
        $this->commands([
            \Modules\RedmineIntegration\Console\SynchronizeTasks::class,
        ]);

        //Register synchronize redmine projects command
        $this->commands([
            \Modules\RedmineIntegration\Console\SynchronizeProjects::class,
        ]);
    }


    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('redmineintegration.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php', 'redmineintegration'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/redmineintegration');

        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/redmineintegration';
        }, \Config::get('view.paths')), [$sourcePath]), 'redmineintegration');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/redmineintegration');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'redmineintegration');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'redmineintegration');
        }
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (!app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
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
