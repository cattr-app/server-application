<?php

namespace App\Http\Requests\TaskComment;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\Task;
use App\Models\TaskComment;

class CreateTaskCommentRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        // Resolve the task without the access scope: the view policy is what decides visibility,
        // and the scoped lookup would return null (not 403) for tasks the user cannot see.
        $task = Task::withoutGlobalScopes()->find($this->input('task_id'));

        return $this->user()->can('create', [TaskComment::class])
            && $task !== null
            && $this->user()->can('view', $task);
    }

    public function _rules(): array
    {
        return ['task_id' => 'required|int|exists:tasks,id', 'content' => 'string'];
    }
}
