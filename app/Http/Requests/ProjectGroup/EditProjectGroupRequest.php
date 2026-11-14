<?php

namespace App\Http\Requests\ProjectGroup;

use App\Http\Requests\CattrFormRequest;
use App\Models\ProjectGroup;
use Illuminate\Validation\Rule;

class EditProjectGroupRequest extends CattrFormRequest
{

    public function _authorize(): bool
    {
        return $this->user()->can('update', ProjectGroup::class);
    }

    public function _rules(): array
    {
        return [
            'id' => ['required', 'int', Rule::exists('project_groups', 'id')->whereNull('deleted_at')],
            'name' => 'sometimes|required|string',
            'parent_id' => ['nullable', 'sometimes', 'integer', Rule::exists('project_groups', 'id')->whereNull('deleted_at')],
        ];
    }
}
