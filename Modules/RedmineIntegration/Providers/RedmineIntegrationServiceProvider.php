<?php

namespace Modules\RedmineIntegration\Providers;

use App\EventFilter\EventServiceProvider as ServiceProvider;
use Config;
use Illuminate\Database\Eloquent\Factory;
use Modules\RedmineIntegration\Console\GenerateSignature;
use Modules\RedmineIntegration\Console\SynchronizePriorities;
use Modules\RedmineIntegration\Console\SynchronizeProjects;
use Modules\RedmineIntegration\Console\SynchronizeStatuses;
use Modules\RedmineIntegration\Console\SynchronizeTasks;
use Modules\RedmineIntegration\Console\SynchronizeTime;
use Modules\RedmineIntegration\Console\SynchronizeUsers;
use Modules\RedmineIntegration\Http\Middleware\ValidateSignature;

class RedmineIntegrationServiceProvider extends ServiceProvider
{
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
        'answer.success.item.list.result.task' => [
            'Modules\RedmineIntegration\Listeners\IntegrationObserver@taskList',
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
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerCommands();

        app('router')->aliasMiddleware(
            'redmineintegration.signature',
            ValidateSignature::class
        );

        parent::boot();
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/redmineintegration');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'redmineintegration');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'redmineintegration');
        }
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/redmineintegration');

        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(static function ($path) {
            return $path . '/modules/redmineintegration';
        }, Config::get('view.paths')), [$sourcePath]), 'redmineintegration');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories(): void
    {
        if (!app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(ScheduleServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(BroadcastServiceProvider::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('redmineintegration.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'redmineintegration'
        );
    }

    protected function registerCommands(): void
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
}
