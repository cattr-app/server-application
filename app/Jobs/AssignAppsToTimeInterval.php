<?php

namespace App\Jobs;

use App\Models\TimeInterval;
use App\Models\TrackedApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AssignAppsToTimeInterval implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $uniqueFor = 60;

    protected TimeInterval $_interval;

    public function __construct(TimeInterval $interval)
    {
        $this->_interval = $interval;
    }

    public function uniqueId(): string
    {
        return $this->_interval->id;
    }

    public function handle(): void
    {
        DB::table('tracked_applications')
            ->whereNull('time_interval_id')
            ->where('user_id', $this->_interval->user_id)
            ->where('created_at', '>', $this->_interval->start_at->setTimezone('UTC'))
            ->where('created_at', '<', $this->_interval->end_at->setTimezone('UTC'))
            ->update(['time_interval_id' => $this->_interval->id]);
    }
}
