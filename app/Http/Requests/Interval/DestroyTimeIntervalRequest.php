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

    /**
     * The desktop client treats "interval_already_deleted" as a successful removal,
     * so report 409 only for an interval that is actually soft-deleted; any other
     * refusal is a regular 403.
     */
    protected function failedAuthorization(): void
    {
        if (TimeInterval::withTrashed()->find(request('id'))?->trashed()) {
            throw new IntervalAlreadyDeletedException;
        }

        parent::failedAuthorization();
    }

    public function _rules(): array
    {
        return [
            'id' => 'required|int|exists:time_intervals,id',
        ];
    }
}
