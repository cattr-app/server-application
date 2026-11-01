<?php

namespace App\Http\Requests\Interval;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\TimeInterval;
use Illuminate\Validation\Rule;

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
        // The result is reused within this request for identical user, task and manual flag.
        $intervals = TimeInterval::whereIn('id', $timeIntervalIds)->get()->keyBy('id');
        $checked = [];

        foreach (request('intervals') as $interval) {
            $timeInterval = $intervals[$interval['id']];
            $taskId = (int)$interval['task_id'];
            $isManual = (bool)$timeInterval->is_manual;
            $key = "$timeInterval->user_id:$taskId:" . (int)$isManual;

            $checked[$key] ??= $this->user()->can('create', [
                TimeInterval::class,
                $timeInterval->user_id,
                $taskId,
                $isManual,
            ]);

            if (!$checked[$key]) {
                return false;
            }
        }

        return true;
    }

    public function _rules(): array
    {
        return [
            'intervals' => 'required|array',
            'intervals.*.id' => ['required', 'int', Rule::exists('time_intervals', 'id')->whereNull('deleted_at')],
            'intervals.*.task_id' => 'required|int|exists:tasks,id'
        ];
    }
}
