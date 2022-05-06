<?php

namespace App\Http\Requests\TaskComment;

use App\Http\Requests\CattrFormRequest;
use App\Models\Status;
use App\Models\User;

class CreateTaskCommentRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->can('create', Status::class);
    }

    public function _rules(): array
    {
        return [];
    }
}
