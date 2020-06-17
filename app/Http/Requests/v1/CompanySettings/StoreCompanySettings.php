<?php

namespace App\Http\Requests\v1\CompanySettings;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanySettings extends FormRequest
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
            'timezone' => 'sometimes|required|timezone',
            'language' => 'sometimes|required|string',
            'work_time' => 'sometimes|int',
            'color' => 'sometimes|required|array',
        ];
    }
}
