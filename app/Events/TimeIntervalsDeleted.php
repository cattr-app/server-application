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
        return [
            new PrivateChannel("TimeIntervalsDeleted.{$this->user->id}"),
        ];
    }
}
