<?php

namespace App\Http\Requests\ProjectGroup;

use App\Helpers\QueryHelper;
use App\Http\Requests\CattrFormRequest;
use App\Models\ProjectGroup;
use Illuminate\Validation\Rule;

class ShowProjectGroupRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->can('view', ProjectGroup::class);
    }

    public function _rules(): array
    {
        return array_merge(
            QueryHelper::getValidationRules(),
            ['id' => ['required', 'int', Rule::exists('project_groups', 'id')->whereNull('deleted_at')]],
        );
    }
}
