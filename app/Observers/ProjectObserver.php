<?php

namespace App\Observers;

use App\Events\ProjectsDeleted;
use App\Events\ProjectsUpdated;
use App\Models\Project;
use Log;

class ProjectObserver
{
    /**
     * Handle the Project "created" event.
     */
    // public function created(Project $project): void
    // {
    //     Log::debug('Project created event');

    //     broadcast()
    // }

    /**
     * Handle the Project "updated" event.
     */
    public function updated(Project $project): void
    {
        Log::debug('Project updated event');

        broadcast(new ProjectsUpdated($project));
    }

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        Log::debug('Project deleted event');

        broadcast(new ProjectsDeleted($project));
    }
}
