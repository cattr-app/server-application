<?php

namespace Modules\JiraIntegration\Providers;

use App\EventFilter\Facades\Filter;
use App\Models\TimeInterval;
use Illuminate\Support\ServiceProvider;
use Modules\JiraIntegration\Console\SyncTasks;
use Modules\JiraIntegration\Console\SyncTime;
use Modules\JiraIntegration\Entities\TaskRelation;
use Modules\JiraIntegration\Entities\TimeRelation;

class JiraIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->registerCommands();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        Filter::listen('answer.success.item.create.timeinterval', static function ($data) {
            /** @var TimeInterval $timeInterval */
            $timeInterval = $data['interval'];
            $taskRelation = TaskRelation::where(['task_id' => $timeInterval->task_id])->first();
            if (isset($taskRelation)) {
                TimeRelation::create([
                    'jira_task_id' => $taskRelation->id,
                    'time_interval_id' => $timeInterval->id,
                    'user_id' => $timeInterval->user_id,
                ]);
            }

            return $data;
        });
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path('JiraIntegration', 'Config/config.php') => config_path('jiraintegration.php'),
        ], 'config');

        $this->mergeConfigFrom(module_path('JiraIntegration', 'Config/config.php'), 'jiraintegration');
    }

    /**
     * Register command
     */
    protected function registerCommands(): void
    {
        $this->commands([
            SyncTasks::class,
            SyncTime::class,
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
