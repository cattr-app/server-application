<?php

namespace Modules\GitlabIntegration\Listeners;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Class IntegrationObserver
*/
class IntegrationObserver
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create the event listener.
     */
    public function __construct() {
    }

    /**
     * Observe task edition
     *
     * @param $task
     *
     * @return mixed
     */
    public function taskEdition($task)
    {
        $relation = DB::table('gitlab_tasks_relations')
            ->where('task_id', $task->id)
            ->first();
        if (isset($relation)) {
            abort(403, 'Access denied to edit a task from GitLab integration');
        }

        return $task;
    }
}
