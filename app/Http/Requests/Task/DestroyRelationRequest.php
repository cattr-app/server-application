<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\Task;
use App\Http\Requests\CattrFormRequest;
use Illuminate\Validation\Rule;

class DestroyRelationRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('update', Task::find(request('parent_id')))
            && $this->user()->can('update', Task::find(request('child_id')));
    }

    public function _rules(): array
    {
        return [
            'parent_id' => [
                'required',
                'int',
                Rule::exists('tasks', 'id'),
            ],
            'child_id' => [
                'required',
                'int',
                Rule::exists('tasks', 'id'),
            ],
        ];
    }
}
