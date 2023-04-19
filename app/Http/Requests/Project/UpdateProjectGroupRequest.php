<?php

namespace App\Http\Requests\Project;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;

class UpdateProjectGroupRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated():bool
    {
        return $this->user()->can('update', $this->route('project'));
    }

    public function _rules(): array
    {
        return [
            'group' => 'sometimes|required|integer|exists:project_groups,id',
        ];
    }
}
