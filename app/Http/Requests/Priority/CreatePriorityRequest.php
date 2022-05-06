<?php

namespace App\Http\Requests\Priority;

use App\Http\Requests\CattrFormRequest;
use App\Models\Priority;

class CreatePriorityRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->can('create', Priority::class);
    }

    public function _rules(): array
    {
        return [
            'name' => 'required|string',
            'color' => 'sometimes|nullable|string|regex:/^#[a-f0-9]{6}$/i',
        ];
    }

    public function attributes(): array
    {
        return [
            'users.*.email' => 'Email'
        ];
    }
}
