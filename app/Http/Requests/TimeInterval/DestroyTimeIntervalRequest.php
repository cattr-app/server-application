<?php

namespace App\Http\Requests\TimeInterval;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\FormRequest;
use App\Models\TimeInterval;

class DestroyTimeIntervalRequest extends FormRequest
{
    use AuthorizesAfterValidation;

    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorizeValidated(): bool
    {
        return $this->user()->can('destroy', TimeInterval::find(request('id')));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'id' => 'required|int|exists:time_intervals,id',
        ];
    }
}
