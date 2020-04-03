<?php

namespace Modules\GitlabIntegration\Listeners;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Pagination\Paginator;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class IntegrationObserver
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;


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
     * @param Collection|Paginator $tasks
     *
     * @return array
     */
    public function taskList($tasks)
    {
        if ($tasks instanceof Paginator) {
            $items = $tasks->getCollection();
        } else {
            $items = $tasks;
        }

        $taskIds = $items->map(static function ($task) {
            return $task->id;
        })->toArray();
        $gitlabTaskIds = DB::table('gitlab_tasks_relations')
            ->whereIn('task_id', $taskIds)
            ->get(['task_id'])
            ->pluck('task_id')
            ->toArray();

        $items->transform(static function ($item) use ($gitlabTaskIds) {
            if (in_array($item->id, $gitlabTaskIds)) {
                $item->integration = 'gitlab';
            }

            return $item;
        });

        if ($tasks instanceof Paginator) {
            $tasks->setCollection($items);
        } else {
            $tasks = $items;
        }

        return $tasks;
    }
}
