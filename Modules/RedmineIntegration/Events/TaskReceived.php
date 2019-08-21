<?php

namespace Modules\RedmineIntegration\Events;

use App\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Task
     */
    protected $task;

    /**
     * Create a new event instance.
     *
     * @param Task $task
     */
    public function __construct($task)
    {
        $this->task = $task;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return PrivateChannel
     */
    public function broadcastOn()
    {
        return new PrivateChannel("task.updates.{$this->task->user_id}");
    }

    /**
     * @return string
     */
    public function broadcastAs()
    {
        return 'task.update';
    }
}
