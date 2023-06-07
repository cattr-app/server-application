<?php

namespace App\Events;

use App\Models\Project;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ProjectDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private Project $project;
    private Collection $users;
    /**
     * Create a new event instance.
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
        $this->users = $project->users->map(fn($el) => $el->id);
    }

    public function broadcastWith(): array
    {
        return [$this->project];
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
            ->where('company_id', '=', $this->project->company_id)
            ->get()
            ->map(fn($el) => $el->getAttributes()['id']);
        
        return array_map(fn($userId) => new PrivateChannel("ProjectDeleted.{$userId}"), array_unique(array_merge($this->users->toArray(), $companyAdminsAndManagersIds->toArray()), SORT_REGULAR));
    }
}
