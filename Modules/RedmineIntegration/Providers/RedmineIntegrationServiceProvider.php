<?php

namespace Modules\RedmineIntegration\Providers;

use App\EventFilter\EventServiceProvider as ServiceProvider;
use Config;
use Illuminate\Database\Eloquent\Factory;
use Modules\RedmineIntegration\Console\{GenerateSignature,
    SynchronizePriorities,
    SynchronizeProjects,
    SynchronizeStatuses,
    SynchronizeTasks,
    SynchronizeTime,
    SynchronizeUsers};
use Modules\RedmineIntegration\Http\Middleware\ValidateSignature;

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

    /**
     * @var array
     */
    protected $listen = [
        'item.create.task' => [
            'Modules\RedmineIntegration\Listeners\IntegrationObserver@taskCreation',
        ],
        'item.edit.task' => [
            'Modules\RedmineIntegration\Listeners\IntegrationObserver@taskEdition',
        ],
        'answer.success.item.show.user' => [
            'Modules\RedmineIntegration\Listeners\IntegrationObserver@userShow',
        ],
        'answer.success.item.edit.user' => [
            'Modules\RedmineIntegration\Listeners\IntegrationObserver@userAfterEdition',
        ],
        'answer.success.item.create.user' => [
            'Modules\RedmineIntegration\Listeners\IntegrationObserver@userAfterEdition',
        ],
        'role.actions.list' => [
            'Modules\RedmineIntegration\Listeners\IntegrationObserver@rulesHook',
        ],
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
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->registerCommands();

        app('router')->aliasMiddleware('redmineintegration.signature',
            ValidateSignature::class);

        parent::boot();
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
            $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'redmineintegration');
        }
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('redmineintegration.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'redmineintegration'
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

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path.'/modules/redmineintegration';
        }, Config::get('view.paths')), [$sourcePath]), 'redmineintegration');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (!app()->environment('production')) {
            app(Factory::class)->load(__DIR__.'/../Database/factories');
        }
    }

    protected function registerCommands()
    {
        //Register synchronize redmine tasks command
        $this->commands([
            SynchronizeTasks::class,
            SynchronizeStatuses::class,
            SynchronizePriorities::class,
            SynchronizeProjects::class,
            SynchronizeUsers::class,
            SynchronizeTime::class,
            GenerateSignature::class
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //Register Schedule service provider
        $this->app->register(ScheduleServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // TODO: What is this?

//        $this->app->singleton(TaskIntegrationHelper::class, function ($app) {
//            return new TaskIntegrationHelper();
//        });
//
//        $this->app->singleton(TimeIntervalIntegrationHelper::class, function ($app) {
//            return new TimeIntervalIntegrationHelper();
//        });
//
//        $this->app->singleton(UserRepository::class, function ($app) {
//            return new UserRepository();
//        });
//
//        $this->app->singleton(ProjectRepository::class, function ($app) {
//            return new ProjectRepository();
//        });
//
//        $this->app->singleton(TaskRepository::class, function ($app) {
//            return new TaskRepository();
//        });
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
