<?php

namespace App\Observers;

use App\Events\TasksCreated;
use App\Events\TasksDeleted;
use App\Events\TasksUpdated;
use App\Models\Task;
use Log;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        Log::debug('Task created event');

        broadcast(new TasksCreated($task));
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        Log::debug('Task updated event');

        broadcast(new TasksUpdated($task));
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        Log::debug('Task deleted event');
        
        broadcast(new TasksDeleted($task));
    }
}
