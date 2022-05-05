<?php

namespace App\Http\Requests\Priority;

use App\Http\Requests\CattrFormRequest;
use App\Models\Priority;
use App\Models\User;

class UpdatePriorityRequest extends CattrFormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function _authorize(): bool
    {
        return $this->user()->can('update', Priority::find(request('id')));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function _rules(): array
    {
        return [
            'id' => 'required|integer|exists:priorities,id',
            'name' => 'required|string',
            'color' => 'sometimes|nullable|string|regex:/^#[a-f0-9]{6}$/i',
        ];
    }
}
