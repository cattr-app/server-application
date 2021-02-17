<?php

namespace App\Http\Requests\Priority;

use App\Http\Requests\FormRequest;

class ShowPriorityRequest extends FormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:priorities,id',
        ];
    }
}
