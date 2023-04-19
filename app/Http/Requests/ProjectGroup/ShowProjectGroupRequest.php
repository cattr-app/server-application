<?php

namespace App\Http\Requests\ProjectGroup;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\ProjectGroup;

class ShowProjectGroupRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('view', ProjectGroup::find(request('id')));
    }

    public function _rules(): array
    {
        return ['id' => 'required|int|exists:project_groups,id'];
    }
}
