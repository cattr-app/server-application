<?php

namespace App\Http\Requests\Reports;

use App\Http\Requests\FormRequest;
use Filter;

class TimeUseReportRequest extends FormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return Filter::process('validation.report.show.time_use', [
            'users' => 'nullable|exists:users,id|array',
            'start_at' => 'required|date',
            'end_at' => 'required|date',
        ]);
    }
}
