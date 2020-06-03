<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class BetweenDate implements Rule
{

    protected string $afterDate;

    protected string $beforeDate;

    /**
     * Create a new rule instance.
     * @param string $afterDate
     * @param string $beforeDate
     */
    public function __construct(string $afterDate, string $beforeDate)
    {
        $this->afterDate = $afterDate;
        $this->beforeDate = $beforeDate;
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
        $endTimestamp = strtotime($value);
        $afterTimestamp = strtotime($this->afterDate);
        $beforeTimestamp = strtotime($this->beforeDate);

        return $endTimestamp > $afterTimestamp && $endTimestamp <= $beforeTimestamp;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The date is not between acceptable boundaries';
    }
}
