<?php

namespace App\Rules;

use App\Models\User;
use App\Models\TimeInterval;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

class TimeIntervalDoesNotExist implements Rule
{
    /**
     * @var User
     */
    private ?User $user;

    /**
     * @var Carbon
     */
    private Carbon $startAt;

    /**
     * @var Carbon
     */
    private Carbon $endAt;

    /**
     * Create a new rule instance.
     *
     * TimeInterval constructor.
     * @param User|null $user
     * @param Carbon $startAt
     * @param Carbon $endAt
     */
    public function __construct(?User $user, Carbon $startAt, Carbon $endAt)
    {
        $this->user = $user;
        $this->startAt = $startAt;
        $this->endAt = $endAt;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return !TimeInterval::where('user_id', optional($this->user)->id)
            ->where(function ($query) {
                $query
                    ->whereBetween('start_at', [$this->startAt, $this->endAt])
                    ->orWhereBetween('end_at', [$this->startAt, $this->endAt])
                    ->orWhere(function ($query) {
                        $query
                            ->where('start_at', '<', $this->startAt)
                            ->where('end_at', '>', $this->endAt);
                    });
            })
            ->exists();
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return trans('validation.time_interval_does_not_exist');
    }
}
