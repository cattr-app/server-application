<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TimeIntervalPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Project::class => ProjectPolicy::class,
        Task::class => TaskPolicy::class,
        User::class => UserPolicy::class,
        TimeInterval::class => TimeIntervalPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
