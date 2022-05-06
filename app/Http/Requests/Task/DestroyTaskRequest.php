<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\Task;
use App\Http\Requests\CattrFormRequest;

class DestroyTaskRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('destroy', Task::find(request('id')));
    }

    public function _rules(): array
    {
        return [
            'id' => 'required|int'
        ];
    }
}
