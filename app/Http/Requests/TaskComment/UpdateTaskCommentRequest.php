<?php

namespace App\Http\Requests\TaskComment;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\Status;
use App\Models\TaskComment;
use App\Models\User;

class UpdateTaskCommentRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        // An unknown id is answered with 404 by the controller
        $comment = TaskComment::find($this->input('id'));

        return $comment === null || $this->user()->can('update', $comment);
    }

    public function _rules(): array
    {
        return [
            'id' => 'required',
            'content' => 'required'
        ];
    }
}
