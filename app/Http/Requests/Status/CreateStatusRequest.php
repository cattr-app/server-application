<?php

namespace App\Http\Requests\Status;

use App\Http\Requests\CattrFormRequest;
use App\Models\Status;
use App\Models\User;

class CreateStatusRequest extends CattrFormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function _authorize(): bool
    {
        return $this->user()->can('create', Status::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function _rules(): array
    {
        return [
            'name' => 'required|string',
            'active' => 'sometimes|boolean',
            'color' => 'sometimes|nullable|string|regex:/^#[a-f0-9]{6}$/i',
        ];
    }
}
