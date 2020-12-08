<?php

namespace App\Http\Requests\TimeInterval;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\FormRequest;
use App\Models\TimeInterval;

class BulkDestroyTimeIntervalRequest extends FormRequest
{
    use AuthorizesAfterValidation;

    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorizeValidated(): bool
    {
        return $this->user()->can('bulkDestroy', [TimeInterval::class, request('intervals')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'intervals' => 'required|array',
            'intervals.*' => 'int|exists:time_intervals,id'
        ];
    }
}
