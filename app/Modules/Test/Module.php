<?php

namespace App\Modules\Test;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class Module extends ServiceProvider
{
    protected $listen = [
        'answer.success.item.create.*' => [
            'App\Modules\Test\Event@modifyAnswer',
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
