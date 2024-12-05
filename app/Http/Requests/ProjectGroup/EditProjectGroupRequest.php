<?php

namespace App\Http\Requests\ProjectGroup;

use App\Http\Requests\CattrFormRequest;
use App\Models\ProjectGroup;

class EditProjectGroupRequest extends CattrFormRequest
{

    public function _authorize(): bool
    {
        return $this->user()->can('update', ProjectGroup::class);
    }

    public function _rules(): array
    {
        return [
            'id' => 'required|int|exists:project_groups,id',
            'name' => 'sometimes|required|string',
            'parent_id' => 'nullable|sometimes|integer|exists:project_groups,id',
        ];
    }
}
