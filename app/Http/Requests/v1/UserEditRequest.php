<?php

namespace App\Http\Requests\v1;

use Illuminate\Http\Request;

class UserEditRequest extends ApiRequest
{
    /**
     * Determine if user authorized to make this request
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
            'id' => 'required|exists:users,id',
            'full_name' => 'required',
            'email' => 'required|unique:users,email,' . Request::input('id'),
            'password' => 'sometimes|min:6',
            'user_language' => 'required'
        ];
    }
}
