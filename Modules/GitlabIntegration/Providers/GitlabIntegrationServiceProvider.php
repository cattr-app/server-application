<?php

namespace Modules\GitlabIntegration\Providers;

use App\EventFilter\EventServiceProvider as ServiceProvider;
use App\EventFilter\Facades\Filter;
use Illuminate\Database\Eloquent\Factory;
use Modules\GitlabIntegration\Console\SynchronizeTime;
use Modules\GitlabIntegration\Console\Syncronize;
use Modules\GitlabIntegration\Helpers\TimeIntervalsHelper;

class GitlabIntegrationServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    protected $listen = [
        'answer.success.item.list.result.task' => [
            'Modules\GitlabIntegration\Listeners\IntegrationObserver@taskList',
        ],
        'item.edit.task' => [
            'Modules\GitlabIntegration\Listeners\IntegrationObserver@taskEdition',
        ],
    ];

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerFactories();
        $this->registerCommands();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        Filter::listen('answer.success.item.create.timeinterval', static function ($data) {
            $timeInterval = $data['interval'];
            $helper = app()->make(TimeIntervalsHelper::class);
            $helper->createUnsyncedInterval($timeInterval);
            return $data;
        });

        parent::boot();
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/gitlabintegration');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'gitlabintegration');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'gitlabintegration');
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('gitlabintegration.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'gitlabintegration'
        );
    }

    /**
     * Register an additional directory of factories.
     */
    public function registerFactories(): void
    {
        if (!app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Register command
     */
    protected function registerCommands(): void
    {
        $this->commands([
            Syncronize::class,
            SynchronizeTime::class,
        ]);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(ScheduleServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
