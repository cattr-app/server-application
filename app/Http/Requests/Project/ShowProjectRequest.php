<?php

namespace App\Http\Requests\Project;

use App\Helpers\QueryHelper;
use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\Project;
use App\Http\Requests\CattrFormRequest;

class ShowProjectRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('view', Project::find(request('id')));
    }

    public function _rules(): array
    {
        return array_merge(QueryHelper::getValidationRules(), [
            'id' => 'required|int|exists:projects,id'
        ]);
    }
}
