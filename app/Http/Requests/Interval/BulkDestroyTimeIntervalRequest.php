<?php

namespace App\Http\Requests\Interval;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\TimeInterval;

class BulkDestroyTimeIntervalRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('bulkDestroy', [TimeInterval::class, request('intervals')]);
    }

    public function _rules(): array
    {
        return [
            'intervals' => 'required|array',
            'intervals.*' => 'int|exists:time_intervals,id'
        ];
    }
}
