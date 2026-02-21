<?php

namespace App\Http\Requests\Reports;

use App\Enums\DashboardSortBy;
use App\Enums\SortDirection;
use App\Helpers\TimezoneHelper;
use App\Http\Requests\CattrFormRequest;
use Filter;
use Illuminate\Validation\Rules\Enum;

class DashboardRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('user_timezone')) {
            $this->merge([
                'user_timezone' => TimezoneHelper::normalize($this->input('user_timezone')),
            ]);
        }
    }

    public function _rules(): array
    {
        return [
            'users' => 'nullable|exists:users,id|array',
            'projects' => 'nullable|exists:projects,id|array',
            'start_at' => 'required|date',
            'end_at' => 'required|date',
            'user_timezone' => 'required|timezone',
            'sort_column' => ['nullable', new Enum(DashboardSortBy::class)],
            'sort_direction' => ['nullable', new Enum(SortDirection::class)],
        ];
    }
}
