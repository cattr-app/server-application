<?php

namespace App\Rules;

use App\Models\User;
use App\Models\TimeInterval;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TimeIntervalDoesNotExist implements ValidationRule
{
    /**
     * @var User
     */
    private User $user;

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
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $alreadyExists = TimeInterval::where('user_id', optional($this->user)->id)
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

        if ($alreadyExists) {
            $fail('validation.time_interval_already_exist')->translate();
        }
    }
}
