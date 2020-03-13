<?php

namespace Modules\TrelloIntegration\Providers;

use App\Models\TimeInterval;
use Filter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\TrelloIntegration\Console\SyncTasks;
use Modules\TrelloIntegration\Console\SyncTime;
use Modules\TrelloIntegration\Entities\TaskRelation;
use Modules\TrelloIntegration\Entities\TimeRelation;

class TrelloIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerFactories();
        $this->registerCommands();

        $this->loadMigrationsFrom(module_path('TrelloIntegration', 'Database/Migrations'));

        Filter::listen('role.actions.list', static function ($rules): array {
            if (!isset($rules['integration']['trello'])) {
                $rules['integration'] += ['trello' => __('Trello integration')];
            }

            return $rules;
        });

        Filter::listen('answer.success.item.create.timeinterval', static function ($data) {
            /** @var TimeInterval $timeInterval */
            $timeInterval = $data['interval'];
            $taskRelation = TaskRelation::where(['task_id' => $timeInterval->task_id])->first();
            if (isset($taskRelation)) {
                TimeRelation::create([
                    'trello_task_id'     => $taskRelation->id,
                    'time_interval_id'   => $timeInterval->id,
                    'user_id'            => $timeInterval->user_id,
                ]);
            }

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
            module_path('TrelloIntegration', 'Config/config.php') => config_path('trellointegration.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path('TrelloIntegration', 'Config/config.php'), 'trellointegration'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/trellointegration');

        $sourcePath = module_path('TrelloIntegration', 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/trellointegration';
        }, \Config::get('view.paths')), [$sourcePath]), 'trellointegration');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/trellointegration');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'trellointegration');
        } else {
            $this->loadTranslationsFrom(module_path('TrelloIntegration', 'Resources/lang'), 'trellointegration');
        }
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        if (! app()->environment('production') && $this->app->runningInConsole()) {
            app(Factory::class)->load(module_path('TrelloIntegration', 'Database/factories'));
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

    protected function registerCommands()
    {
        $this->commands([
            SyncTasks::class,
            SyncTime::class,
        ]);
    }
}
