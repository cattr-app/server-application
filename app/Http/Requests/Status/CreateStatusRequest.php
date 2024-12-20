<?php

namespace App\Http\Requests\Status;

use App\Http\Requests\CattrFormRequest;
use App\Models\Status;
use App\Models\User;
use Illuminate\Validation\Rule;

class CreateStatusRequest extends CattrFormRequest
{

    public function _authorize(): bool
    {
        return $this->user()->can('create', Status::class);
    }

    public function _rules(): array
    {
        return [
            'name' => 'required|string',
            'order' => [
                'sometimes',
                'integer',
            ],
            'active' => 'sometimes|boolean',
            'color' => 'sometimes|nullable|string|regex:/^#[a-f0-9]{6}$/i',
        ];
    }
}
