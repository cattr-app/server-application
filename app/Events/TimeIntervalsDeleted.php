<?php

namespace App\Events;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimeIntervalsDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private TimeInterval $timeInterval;

    private User $user;
    /**
     * Create a new event instance.
     */
    public function __construct(TimeInterval $timeInterval)
    {
        $this->timeInterval = $timeInterval;
        $this->user = $timeInterval->user;
    }

    public function broadcastWith(): array
    {
        return [$this->timeInterval];
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $companyAdminsAndManagersIds = User::select('id')->admin()->manager()->where('company_id', '=', $this->user->company_id)->get()->toArray();
        
        return array_map(fn($user) => new PrivateChannel("TimeIntervalsDeleted.{$user['id']}"), array_unique(array_merge([['id' => $this->user->id, 'online' => $this->user->online]], $companyAdminsAndManagersIds), SORT_REGULAR));
    }
}
