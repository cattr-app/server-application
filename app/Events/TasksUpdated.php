<?php

namespace App\Events;

use App\Models\Task;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TasksUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private array $result;
    private Task $task;
    private Collection $users;
    /**
     * Create a new event instance.
     */
    public function __construct(Task $task)
    {
        $this->result = [
            'model' => Task::query()->where('id', '=', $task->id)->with([
                "priority",
                "project",
                "users",
                "status",
            ])->first()->append('can'),
            'modelShow' => $task->task()
        ];

        $this->users = $task->users->map(fn($el) => $el->id);
        $this->task = $task;
    }

    public function broadcastWith(): array
    {
        return $this->result;
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $companyAdminsAndManagersIds = User::select('id')
            ->admin()
            ->manager()
            ->where('company_id', '=', $this->task->project->company_id)
            ->get()
            ->map(fn($el) => $el->getAttributes()['id']);
        return array_map(fn($userId) => new PrivateChannel("TasksUpdated.{$userId}"), array_unique(array_merge($this->users->toArray(), $companyAdminsAndManagersIds->toArray()), SORT_REGULAR));
    }
}
