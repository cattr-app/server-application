<?php

namespace App\Providers;

use App\Helpers\Lock\ILock;
use App\Helpers\Lock\Lock;
use Illuminate\Support\ServiceProvider;

class LockServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ILock::class, function ($app) {
            return new Lock();
        });
    }
}
