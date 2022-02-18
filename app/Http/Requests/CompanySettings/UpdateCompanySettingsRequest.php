<?php

namespace App\Http\Requests\CompanySettings;

use App\Http\Requests\FormRequest;
use Filter;

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
        return Filter::process('validation.item.edit.settings', [
            'timezone' => 'sometimes|required|timezone',
            'work_time' => 'sometimes|int',
            'auto_thinning' => 'sometimes|boolean',
            'language' => 'sometimes|string',
            'default_priority_id' => 'sometimes|int',
        ]);
    }
}
