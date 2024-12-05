<?php

namespace App\Http\Requests\Reports\UniversalReport;

use App\Http\Requests\CattrFormRequest;

class UniversalReportDestroyRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return auth()->check();
    }

    public function _rules(): array
    {
        return [
            'id' => 'required|int|exists:universal_reports,id',
        ];
    }
}
