<?php

namespace App\Http\Requests\Interval;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\TimeInterval;

class BulkEditTimeIntervalRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        $timeIntervalIds = [];

        foreach (request('intervals') as $interval) {
            $timeIntervalIds[] = $interval['id'];
        }

        if (!$this->user()->can('bulkUpdate', [TimeInterval::class, $timeIntervalIds])) {
            return false;
        }

        // Moving an interval to another task is the same as tracking time there,
        // so the target task must pass the same check as time-intervals/create.
        foreach (request('intervals') as $interval) {
            $timeInterval = TimeInterval::find($interval['id']);

            if (!$this->user()->can('create', [
                TimeInterval::class,
                $timeInterval->user_id,
                (int)$interval['task_id'],
                false,
            ])) {
                return false;
            }
        }

        return true;
    }

    public function _rules(): array
    {
        return [
            'intervals' => 'required|array',
            'intervals.*.id' => 'required|int|exists:time_intervals,id',
            'intervals.*.task_id' => 'required|int|exists:tasks,id'
        ];
    }
}
