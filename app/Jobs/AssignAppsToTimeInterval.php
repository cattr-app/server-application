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

    public function __construct(protected TimeInterval $interval)
    {
    }

    public function uniqueId(): string
    {
        return $this->interval->id;
    }

    public function handle(): void
    {
        DB::table('tracked_applications')
            ->whereNull('time_interval_id')
            ->where('user_id', $this->interval->user_id)
            ->where('created_at', '>', $this->interval->start_at->setTimezone('UTC'))
            ->where('created_at', '<', $this->interval->end_at->setTimezone('UTC'))
            ->update(['time_interval_id' => $this->interval->id]);
    }
}
