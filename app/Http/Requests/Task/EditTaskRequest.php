<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\Task;
use App\Http\Requests\CattrFormRequest;

class EditTaskRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('update', Task::find(request('id')));
    }

    public function _rules(): array
    {
        return [
            'id' => 'required|int|exists:tasks,id',
            'project_id' => 'sometimes|required|exists:projects,id|',
            'task_name' => 'sometimes|required|string',
            'description' => 'string',
            'users' => 'sometimes|array',
            'users.*' => 'required|exists:users,id',
            'active' => 'bool',
            'important' => 'bool',
            'priority_id' => 'sometimes|nullable|exists:priorities,id',
            'status_id' => 'sometimes|required|exists:statuses,id',
            'relative_position' => 'sometimes|required|numeric',
            'due_date' => 'sometimes|nullable|date',
        ];
    }
}
