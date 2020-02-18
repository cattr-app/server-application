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

    /**
     * Observe task list
     *
     * @param array|Paginator $tasks
     *
     * @return array
     */
    public function taskList($tasks)
    {
        $items = $tasks->getCollection();
        $taskIds = $items->map(function ($task) { return $task->id; })->toArray();
        $gitlabTaskIds = DB::table('gitlab_tasks_relations')
            ->whereIn('task_id', $taskIds)
            ->get(['task_id'])
            ->pluck('task_id')
            ->toArray();

        $items->transform(function ($item) use ($gitlabTaskIds) {
            if (in_array($item->id, $gitlabTaskIds)) {
                $item->integration = 'gitlab';
            }

            return $item;
        });

        $tasks->setCollection($items);

        return $tasks;
    }
}
