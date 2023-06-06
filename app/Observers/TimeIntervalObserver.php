<?php

namespace App\Observers;

use App\Events\TimeIntervalsCreated;
use App\Events\TimeIntervalsDeleted;
use App\Events\TimeIntervalsUpdated;
use App\Models\TimeInterval;
use Log;

class TimeIntervalObserver
{
    /**
     * Handle the TimeInterval "created" event.
     */
    public function created(TimeInterval $timeInterval): void
    {
        Log::debug('TimeInterval created event');

        broadcast(new TimeIntervalsCreated($timeInterval));
        // broadcast(new TimeIntervalsCreated(TimeInterval::find($data->id)));
    }

    /**
     * Handle the TimeInterval "updated" event.
     */
    public function updated(TimeInterval $timeInterval): void
    {
        Log::debug('TimeInterval updated event');

        broadcast(new TimeIntervalsUpdated($timeInterval));
    }

    /**
     * Handle the TimeInterval "deleted" event.
     */
    public function deleted(TimeInterval $timeInterval): void
    {
        Log::debug('TimeInterval deleted event');

        broadcast(new TimeIntervalsDeleted($timeInterval));
    }
}
