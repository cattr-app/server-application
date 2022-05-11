<?php

namespace App\Http\Requests\TaskComment;

use App\Http\Requests\CattrFormRequest;
use App\Models\Task;
use App\Models\TaskComment;

class CreateTaskCommentRequest extends CattrFormRequest
{

    public function _authorize(): bool
    {
        return $this->user()->can('create', [TaskComment::class]);
    }

    public function _rules(): array
    {
        return ['task_id' => 'required|int|exists:tasks,id', 'content' => 'string'];
    }


}
