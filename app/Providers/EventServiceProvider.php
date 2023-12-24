<?php

namespace App\Providers;

use App\Models\TimeInterval;
use App\Models\User;
use App\Observers\TimeIntervalObserver;
use App\Observers\UserObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [];

    protected $subscribe = [];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        TimeInterval::observe(TimeIntervalObserver::class);
    }

    public function shouldDiscoverEvents(): bool
    {
        return true;
    }
}
