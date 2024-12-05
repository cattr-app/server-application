<?php

namespace App\Http\Requests\Reports\UniversalReport;

use App\Enums\UniversalReportBase;
use App\Http\Requests\CattrFormRequest;
use Exception;
use Illuminate\Validation\Rules\Enum;

class UniversalReportRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return auth()->check();
    }

    public function _rules(): array
    {
        return [
            'id' => 'required|exists:universal_reports,id|int',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date',
        ];
    }
}
