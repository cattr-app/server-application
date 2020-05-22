<?php

namespace App\EventFilter;

use App\EventFilter\Facades\Filter;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\InvitationCreated' => ['App\Listeners\SendInvitationMail'],
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
    public function boot()
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
     * Get the events and handlers.
     *
     * @return array
     */
    public function listens()
    {
        return $this->listen;
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        //
    }
}
