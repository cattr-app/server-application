<?php

namespace App\Providers;

use App\Contracts\AttachmentService as AttachmentServiceContract;
use App\Services\AttachmentService;
use Illuminate\Support\ServiceProvider;

class AttachmentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(AttachmentServiceContract::class, AttachmentService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
