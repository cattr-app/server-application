<?php

namespace App\Observers;

use App\Events\TimeIntervalCreated;
use App\Events\TimeIntervalDeleted;
use App\Events\TimeIntervalUpdated;
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

        broadcast(new TimeIntervalCreated($timeInterval));
    }

    /**
     * Handle the TimeInterval "updated" event.
     */
    public function updated(TimeInterval $timeInterval): void
    {
        Log::debug('TimeInterval updated event');

        broadcast(new TimeIntervalUpdated($timeInterval));
    }

    /**
     * Handle the TimeInterval "deleted" event.
     */
    public function deleted(TimeInterval $timeInterval): void
    {
        Log::debug('TimeInterval deleted event');

        broadcast(new TimeIntervalDeleted($timeInterval));
    }
}
