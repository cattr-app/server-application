<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\Task;
use App\Http\Requests\CattrFormRequest;
use Illuminate\Validation\Rule;

class CreateTaskRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('create', [Task::class, $this->get('project_id')]);
    }

    public function _rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'project_phase_id' => [
                'sometimes',
                'nullable',
                Rule::exists('project_phases', 'id')
                    ->where('project_id', $this->input('project_id')),
            ],
            'task_name' => 'required|string',
            'description' => 'string',
            'users' => 'sometimes|array',
            'users.*' => 'exists:users,id',
            'active' => 'bool',
            'important' => 'bool',
            'priority_id' => 'sometimes|nullable|exists:priorities,id',
            'status_id' => 'sometimes|required|exists:statuses,id',
            'relative_position' => 'sometimes|required|integer',
            'due_date' => 'sometimes|nullable|date',
            'estimate' => 'sometimes|nullable|integer|gte:0',
        ];
    }
}
