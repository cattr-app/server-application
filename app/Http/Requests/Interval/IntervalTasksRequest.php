<?php

namespace App\Http\Requests\Interval;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\TimeInterval;

class IntervalTasksRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('viewAny', TimeInterval::class);
    }

    public function _rules(): array
    {
        return [
            'start_at' => 'date',
            'end_at' => 'date',
            'project_id' => 'exists:projects,id',
            'task_id' => 'exists:tasks,id',
            'user_id' => 'required|integer|exists:users,id',
        ];
    }
}
