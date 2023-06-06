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
use Log;

class ProjectsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private Project $project;
    private array $result;
    private Collection $users;
    /**
     * Create a new event instance.
     */
    public function __construct(Project $project)
    {
        $this->result = [
            'model' => Project::query()->where('id', '=', $project->id)->with([
                "defaultPriority",
                "users",
                "statuses",
            ])->withCount('tasks')->first()->append('can'),
            'modelShow' => $project->project()
        ];
        $this->project = $project;
        $this->users = $project->users->map(fn($el) => $el->id);
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
            ->where('company_id', '=', $this->project->company_id)
            ->get()
            ->map(fn($el) => $el->getAttributes()['id']);

        return array_map(fn($userId) => new PrivateChannel("ProjectsUpdated.{$userId}"), array_unique(array_merge($this->users->toArray(), $companyAdminsAndManagersIds->toArray()), SORT_REGULAR));
    }
}
