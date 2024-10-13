<?php

namespace App\Providers;

use App\Contracts\AttachmentService as AttachmentServiceContract;
use App\Helpers\AttachmentHelper;
use App\Services\AttachmentService;
use Illuminate\Database\Eloquent\Relations\Relation;
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
//      TODO: [ ] check if we can add more from Cattr modules
        Relation::morphMap(AttachmentHelper::ABLE_BY);
    }
}
