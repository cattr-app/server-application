<?php

namespace App\Http\Requests\CompanySettings;

use App\Http\Requests\FormRequest;

class UpdateCompanySettingsRequest extends FormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->user()->hasRole('admin');
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
            'work_time' => 'sometimes|int',
            'color' => 'sometimes|required|array',
            'auto_thinning' => 'sometimes|boolean',
        ];
    }
}
