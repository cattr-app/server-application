<?php

namespace App\Http\Requests\TaskComment;

use App\Http\Requests\CattrFormRequest;
use App\Models\Status;
use App\Models\TaskComment;

class ShowTaskCommentRequestStatus extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->can('view', TaskComment::class);
    }

    public function _rules(): array
    {
        return [];
    }
}
