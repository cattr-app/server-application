<?php

namespace App\Http\Requests\TaskComment;

use App\Http\Requests\CattrFormRequest;
use App\Models\Status;
use App\Models\TaskComment;

class ListTaskCommentRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->can('viewAny', TaskComment::class);
    }

    public function _rules(): array
    {
        return [];
    }
}
