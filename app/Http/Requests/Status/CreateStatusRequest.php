<?php

namespace App\Http\Requests\Status;

use App\Http\Requests\FormRequest;

class CreateStatusRequest extends FormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = auth()->user();
        return $user->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'active' => 'sometimes|boolean',
        ];
    }
}
