<?php

namespace App\Http\Requests\Status;

use App\Http\Requests\CattrFormRequest;
use App\Models\Status;
use App\Models\User;

class UpdateStatusRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->can('update', Status::class);
    }

    public function _rules(): array
    {
        return [
            'id' => 'required|integer|exists:statuses,id',
            'name' => 'required|string',
            'active' => 'sometimes|boolean',
            'color' => 'sometimes|nullable|string|regex:/^#[a-f0-9]{6}$/i',
        ];
    }
}
