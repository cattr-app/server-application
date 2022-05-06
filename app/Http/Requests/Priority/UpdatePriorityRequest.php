<?php

namespace App\Http\Requests\Priority;

use App\Http\Requests\CattrFormRequest;
use App\Models\Priority;
use App\Models\User;

class UpdatePriorityRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->can('update', Priority::find(request('id')));
    }

    public function _rules(): array
    {
        return [
            'id' => 'required|integer|exists:priorities,id',
            'name' => 'required|string',
            'color' => 'sometimes|nullable|string|regex:/^#[a-f0-9]{6}$/i',
        ];
    }
}
