<?php

namespace App\Jobs;

use App\Models\TimeInterval;
use App\Models\TrackedApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class AssignAppsToTimeInterval implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $uniqueFor = 300;

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
        TrackedApplication::query()
            ->whereNull('time_interval_id')
            ->where('user_id', $this->_interval->user_id)
            ->where('created_at', '>=', $this->_interval->start_at)
            ->where('created_at', '<=', $this->_interval->end_at)
            ->update(['time_interval_id' => $this->_interval->id]);
    }
}
