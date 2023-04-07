<?php

namespace App\Providers;

use App\Observers\AttachmentObserver;
use Filter;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [];

    protected $subscribe = [AttachmentObserver::class];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
//      TODO: [ ] Create folder for Filters and extract them from AttachmentObserver
        Filter::subscribe(AttachmentObserver::class);
    }


    public function shouldDiscoverEvents(): bool
    {
        return true;
    }
}
