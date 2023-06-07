<?php

namespace App\Observers;

use App\Events\ProjectDeleted;
use App\Events\ProjectUpdated;
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

        broadcast(new ProjectUpdated($project));
    }

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        Log::debug('Project deleted event');

        broadcast(new ProjectDeleted($project));
    }
}
