<?php

namespace App\Observers;

use App\Contracts\ScreenshotService;
use App\Models\TimeInterval;
use App\Models\CronTaskWorkers;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;

class TimeIntervalObserver
{

    /**
     * Handle the TimeInterval "created" event.
     *
     * @param TimeInterval $timeInterval
     * @return void
     */
    public function created(TimeInterval $timeInterval): void
    {
        $viewRecord = CronTaskWorkers::firstOrNew(
            [
                'user_id' => $timeInterval->user_id,
                'task_id' => $timeInterval->task_id
            ]
        );

        $viewRecord->user_id = $timeInterval->user_id;
        $viewRecord->task_id = $timeInterval->task_id;

        $viewRecord->offset += Carbon::parse($timeInterval->end_at)->diffInSeconds($timeInterval->start_at);

        $viewRecord->save();
    }

    /**
     * Handle the TimeInterval "updated" event.
     *
     * @param TimeInterval $timeInterval
     * @return void
     */
    public function updated(TimeInterval $timeInterval): void
    {
        $oldInterval = new TimeInterval($timeInterval->getOriginal());

        $this->deleted($oldInterval);
        $this->created($timeInterval);
    }

    /**
     * Handle the TimeInterval "deleting" event.
     *
     * @param TimeInterval $timeInterval
     * @return void
     * @throws BindingResolutionException
     */
    public function deleting(TimeInterval $timeInterval): void
    {
        $screenshotService = app()->make(ScreenshotService::class);
        $screenshotService->destroyScreenshot($timeInterval);
    }

    /**
     * Handle the TimeInterval "deleted" event.
     *
     * @param TimeInterval $timeInterval
     * @return void
     */
    public function deleted(TimeInterval $timeInterval): void
    {
        $viewRecord = CronTaskWorkers::firstWhere([
            ['user_id', '=', $timeInterval->user_id],
            ['task_id', '=', $timeInterval->task_id]
        ]);

        if ($viewRecord === null) {
            return;
        }

        $viewRecord->offset -= Carbon::parse($timeInterval->end_at)->diffInSeconds($timeInterval->start_at);

        $viewRecord->save();
    }
}
