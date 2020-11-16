<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\Task;
use App\Http\Requests\FormRequest;

class CreateTaskRequest extends FormRequest
{
    use AuthorizesAfterValidation;
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorizeValidated(): bool
    {
        return $this->user()->can('create', [Task::class, request('project_id')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'task_name' => 'required|string',
            'description' => 'string',
            'user_id' => 'required|exists:users,id',
            'active' => 'bool',
            'important' => 'bool',
            'priority_id' => 'required|exists:priorities,id',
        ];
    }
}
