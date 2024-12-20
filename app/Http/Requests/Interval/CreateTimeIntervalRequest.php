<?php

namespace App\Http\Requests\Interval;

use AllowDynamicProperties;
use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\TimeInterval;
use App\Models\User;
use App\Rules\TimeIntervalDoesNotExist;
use Carbon\Carbon;
use Settings;

#[AllowDynamicProperties] class CreateTimeIntervalRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can(
            'create',
            [
                TimeInterval::class,
                $this->get('user_id'),
                $this->get('task_id'),
                $this->get('is_manual', false),
            ],
        );
    }

    public function _rules(): array
    {
        $timezone = Settings::scope('core')->get('timezone', 'UTC');

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
                    Carbon::parse($this->start_at)->setTimezone($timezone),
                    Carbon::parse($this->end_at)->setTimezone($timezone),
                ),
            ],
            'activity_fill' => 'nullable|int|between:0,100',
            'mouse_fill' => 'nullable|int|between:0,100',
            'keyboard_fill' => 'nullable|int|between:0,100',
            'is_manual' => 'sometimes|bool',
            'location' => 'sometimes|array',
            'screenshot' => 'sometimes|required|image',
        ];
    }

    public function getRules($user_id, $start_at, $end_at): array
    {
        $this->user_id = $user_id;
        $this->start_at = $start_at;
        $this->end_at = $end_at;

        return $this->_rules();
    }
}
