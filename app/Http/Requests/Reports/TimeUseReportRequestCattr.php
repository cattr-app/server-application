<?php

namespace App\Http\Requests\Reports;

use App\Http\Requests\CattrFormRequest;
use Filter;

class TimeUseReportRequestCattr extends CattrFormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function _authorize(): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function _rules(): array
    {
        return [
            'users' => 'nullable|exists:users,id|array',
            'start_at' => 'required|date',
            'end_at' => 'required|date',
        ];
    }
}
