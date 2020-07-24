<?php

namespace App\Http\Requests\TimeInterval;

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
     */
    public function rules(): array
    {
        return [
            'task_id' => 'required|exists:tasks,id',
            'user_id' => 'required|exists:users,id',
            'start_at' => 'required|date',
            'end_at' => 'required|date',
            'activity_fill' => 'int|between:0,100',
            'mouse_fill' => 'int|between:0,100',
            'keyboard_fill' => 'int|between:0,100',
            'is_manual' => 'bool',
        ];
    }
}
