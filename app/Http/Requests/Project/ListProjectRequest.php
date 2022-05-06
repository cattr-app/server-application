<?php

namespace App\Http\Requests\Project;

use App\Helpers\QueryHelper;
use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\Project;
use App\Http\Requests\CattrFormRequest;

class ListProjectRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('viewAny', Project::class);
    }

    public function _rules(): array
    {
        return QueryHelper::getValidationRules();
    }
}
