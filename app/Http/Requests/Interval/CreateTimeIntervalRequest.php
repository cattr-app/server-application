<?php

namespace App\Http\Requests\Interval;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\TimeInterval;
use App\Models\User;
use App\Rules\TimeIntervalDoesNotExist;
use Carbon\Carbon;
use Exception;

class CreateTimeIntervalRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can(
            'create',
            [TimeInterval::class, $this->only(['user_id', 'task_id', 'is_manual'])]
        );
    }

    public function _rules(): array
    {
        return [
            'task_id' => 'required|exists:tasks,id',
            'user_id' => 'required|exists:users,id',
            'start_at' => 'required|date|bail|before:end_at',
            'end_at' => [
                'required',
                'date',
                'bail',
                'after:start_at',
                new TimeIntervalDoesNotExist(
                    User::find($this->user_id),
                    Carbon::parse($this->start_at),
                    Carbon::parse($this->end_at)
                ),
            ],
            'activity_fill' => 'int|between:0,100',
            'mouse_fill' => 'int|between:0,100',
            'keyboard_fill' => 'int|between:0,100',
            'is_manual' => 'sometimes|bool',
            'location' => 'sometimes|array',
            'screenshot' => 'sometimes|required|image',
        ];
    }
}