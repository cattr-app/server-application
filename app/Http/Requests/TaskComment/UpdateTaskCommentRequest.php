<?php

namespace App\Http\Requests\TaskComment;

use App\Http\Requests\CattrFormRequest;
use App\Models\Status;
use App\Models\TaskComment;
use App\Models\User;

class UpdateTaskCommentRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->can('update', TaskComment::class);
    }

    public function _rules(): array
    {
        return [];
    }
}
