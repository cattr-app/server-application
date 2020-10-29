<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\Task;
use App\Http\Requests\FormRequest;

class EditTaskRequest extends FormRequest
{
    use AuthorizesAfterValidation;

    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorizeValidated(): bool
    {
        return $this->user()->can('update', Task::find(request('id')));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'id' => 'required|int|exists:tasks,id',
            'project_id' => 'sometimes|required|exists:projects,id|',
            'task_name' => 'sometimes|required|string',
            'description' => 'present',
            'user_id' => 'sometimes|required|exists:users,id',
            'active' => 'bool',
            'important' => 'bool',
            'priority_id' => 'sometimes|required|exists:priorities,id',
        ];
    }
}
