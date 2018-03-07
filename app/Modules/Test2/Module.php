<?php

namespace App\Modules\Test2;

use App\EventFilter\EventServiceProvider as ServiceProvider;

class Module extends ServiceProvider
{
    protected $listen = [
        'answer.success.item.create.test' => [
            'App\Modules\Test2\Event@modifyAnswer',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
