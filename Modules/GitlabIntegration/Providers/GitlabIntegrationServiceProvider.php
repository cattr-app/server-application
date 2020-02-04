<?php

namespace Modules\GitlabIntegration\Providers;

use App\EventFilter\Facades\Filter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\GitlabIntegration\Console\SynchronizeTime;
use Modules\GitlabIntegration\Console\Syncronize;
use Modules\GitlabIntegration\Helpers\TimeIntervalsHelper;

/**
 * Class GitlabIntegrationServiceProvider
*/
class GitlabIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerFactories();
        $this->registerCommands();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        Filter::listen('role.actions.list', static function ($rules) {
            if (!isset($rules['integration']['gitlab'])) {
                $rules['integration'] += [
                    'gitlab' => __('GitLab integration')
                ];
            }
            return $rules;
        });

        Filter::listen('answer.success.item.create.timeinterval', static function ($data) {
            $timeInterval = $data['interval'];
            $helper = app()->make(TimeIntervalsHelper::class);
            $helper->createUnsyncedInterval($timeInterval);
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
        $this->app->register(ScheduleServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('gitlabintegration.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php', 'gitlabintegration'
        );
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/gitlabintegration');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'gitlabintegration');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'gitlabintegration');
        }
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        if (!app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Register command
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands([
            Syncronize::class,
            SynchronizeTime::class,
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
