<?php

namespace App\Http\Requests\TimeInterval;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\TimeInterval;

class BulkEditTimeIntervalRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorizeValidated(): bool
    {
        $timeIntervalIds = [];

        foreach (request('intervals') as $interval) {
            $timeIntervalIds[] = $interval['id'];
        }

        return $this->user()->can('bulkUpdate', [TimeInterval::class, $timeIntervalIds]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function _rules(): array
    {
        return [
            'intervals' => 'required|array',
            'intervals.*.id' => 'required|int|exists:time_intervals,id',
            'intervals.*.task_id' => 'required|int|exists:tasks,id'
        ];
    }
}
