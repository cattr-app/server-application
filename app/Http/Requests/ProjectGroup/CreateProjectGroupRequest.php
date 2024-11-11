<?php

namespace App\Http\Requests\ProjectGroup;

use App\Http\Requests\CattrFormRequest;
use App\Models\ProjectGroup;

class CreateProjectGroupRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->can('create', ProjectGroup::class);
    }

    public function _rules(): array
    {
        return [
            'name' => 'required|string',
            'parent_id' => 'nullable|sometimes|integer|exists:project_groups,id',
        ];
    }
}
