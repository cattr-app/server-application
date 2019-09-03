<?php

namespace App\Providers;

use App\Helpers\Lock\LockInterface;
use App\Helpers\Lock\Lock;
use Illuminate\Support\ServiceProvider;

class LockServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(LockInterface::class, function ($app) {
            return new Lock();
        });
    }
}
