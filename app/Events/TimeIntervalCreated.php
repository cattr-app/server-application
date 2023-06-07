<?php

namespace App\Events;

use App\Models\Project;
use App\Models\TimeInterval;
use App\Models\User;
use App\Reports\DashboardExport;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Settings;

class TimeIntervalCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $timeInterval;

    private $companyTimezone;

    private User $user;
    /**
     * Create a new event instance.
     */
    public function __construct(TimeInterval $timeInterval)
    {
        $this->companyTimezone = Settings::scope('core')->get('timezone', 'UTC');

        $this->timeInterval = DashboardExport::init(
            [$timeInterval->user_id],
            Project::all()->pluck('id')->toArray(),
            Carbon::parse($timeInterval->start_at)->setTimezone($this->companyTimezone),
            Carbon::parse($timeInterval->end_at)->setTimezone($this->companyTimezone),
            $this->companyTimezone,
            $timeInterval->user->timezone ?? 'UTC',
        )->collection([['time_intervals.id', '=', $timeInterval->id]])->all();

        $this->user = $timeInterval->user;
    }

    public function broadcastWith(): array
    {
        return $this->timeInterval;
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $companyAdminsAndManagersIds = User::select('id')->admin()->manager()->where('company_id', '=', $this->user->company_id)->get()->toArray();

        return array_map(fn($user) => new PrivateChannel("TimeIntervalCreated.{$user['id']}"), array_unique(array_merge([['id' => $this->user->id, 'online' => $this->user->online]], $companyAdminsAndManagersIds), SORT_REGULAR));
    }
}
