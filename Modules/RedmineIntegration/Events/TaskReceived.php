<?php

namespace Modules\RedmineIntegration\Events;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskReceived implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Task $task;
    public Project $project;

    /**
     * Create a new event instance.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->project = $task->project;
    }

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("task.updates.{$this->task->user_id}");
    }

    public function broadcastAs(): string
    {
        return 'task.update';
    }
}
