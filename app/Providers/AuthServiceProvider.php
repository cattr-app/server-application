<?php

namespace App\Providers;

use App\Models\Invitation;
use App\Models\Priority;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TimeInterval;
use App\Models\User;
use App\Policies\InvitationPolicy;
use App\Policies\PriorityPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\StatusPolicy;
use App\Policies\TaskCommentPolicy;
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
        Priority::class => PriorityPolicy::class,
        Status::class => StatusPolicy::class,
        Invitation::class => InvitationPolicy::class,
        TaskComment::class => TaskCommentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
