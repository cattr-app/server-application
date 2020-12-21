<?php

namespace App\EventFilter;

use Filter;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\InvitationCreated;
use App\Listeners\SendInvitationMail;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        InvitationCreated::class => [SendInvitationMail::class],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [];

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();

        foreach ($this->listens() as $event => $listeners) {
            foreach ($listeners as $listener) {
                Filter::listen($event, $listener);
            }
        }

        foreach ($this->subscribe as $subscriber) {
            Filter::subscribe($subscriber);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        parent::register();
    }
}
