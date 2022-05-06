<?php

namespace App\Http\Requests\Reports;

use App\Http\Requests\CattrFormRequest;
use Filter;

class ProjectReportRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return true;
    }

    public function _rules(): array
    {
        return [
            'users' => 'nullable|exists:users,id|array',
            'projects' => 'nullable|exists:projects,id|array',
            'start_at' => 'required|date',
            'end_at' => 'required|date',
        ];
    }
}
