<?php

namespace App\Http\Requests\Priority;

use App\Http\Requests\CattrFormRequest;
use App\Models\Priority;

class CreatePriorityRequest extends CattrFormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function _authorize(): bool
    {
        return $this->user()->can('create', Priority::class);
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
            'color' => 'sometimes|nullable|string|regex:/^#[a-f0-9]{6}$/i',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'users.*.email' => 'Email'
        ];
    }
}
