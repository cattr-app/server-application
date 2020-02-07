<?php

namespace Modules\JiraIntegration\Providers;

use App\EventFilter\Facades\Filter;
use App\Models\TimeInterval;
use Illuminate\Support\ServiceProvider;
use Modules\JiraIntegration\Console\{SyncTasks, SyncTime};
use Modules\JiraIntegration\Entities\{TaskRelation, TimeRelation};

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

        Filter::listen('role.actions.list', static function ($rules) {
            if (!isset($rules['integration']['jira'])) {
                $rules['integration'] += ['jira' => __('Jira integration')];
            }

            return $rules;
        });

        Filter::listen('answer.success.item.create.timeinterval', static function ($data) {
            /** @var TimeInterval $timeInterval */
            $timeInterval = $data['interval'];
            $taskRelation = TaskRelation::where(['task_id' => $timeInterval->task_id])->first();
            if (isset($taskRelation)) {
                TimeRelation::create([
                    'jira_task_id'     => $taskRelation->id,
                    'time_interval_id' => $timeInterval->id,
                    'user_id'          => $timeInterval->user_id,
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

        $this->mergeConfigFrom(module_path('JiraIntegration', 'Config/config.php'), 'jiraintegration');
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
            SyncTime::class,
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
