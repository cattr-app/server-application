<?php

namespace App\Http\Requests\TimeInterval;

use App\Models\User;
use App\Rules\TimeIntervalDoesNotExist;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class CreateTimeIntervalRequest extends FormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     * @throws \Exception
     */
    public function rules(): array
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
            'is_manual' => 'bool',
        ];
    }
}
