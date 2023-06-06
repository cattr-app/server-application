<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Observers\ProjectObserver;
use App\Observers\TaskObserver;
use App\Observers\TimeIntervalObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [];

    protected $subscribe = [];

    public function shouldDiscoverEvents(): bool
    {
        return true;
    }

    public function boot()
    {
        TimeInterval::observe(TimeIntervalObserver::class);
        Project::observe(ProjectObserver::class);
        Task::observe(TaskObserver::class);
    }
}
