<?php

namespace App\Http\Requests\Interval;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\TimeInterval;
use App\Exceptions\Entities\IntervalAlreadyDeletedException;

class DestroyTimeIntervalRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('destroy', TimeInterval::find(request('id')));
    }

    protected function failedAuthorization(): void
    {
        throw new IntervalAlreadyDeletedException;
    }

    public function _rules(): array
    {
        return [
            'id' => 'required|int|exists:time_intervals,id',
        ];
    }
}
