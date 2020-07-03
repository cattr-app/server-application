<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use DB;

class DoesNotExist implements Rule
{

    protected string  $table;

    protected int $id;

    /**
     * Create a new rule instance.
     * @param string $table
     * @param int $id
     */
    public function __construct(string $table, int $id)
    {
        $this->table = $table;
        $this->id = $id;
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
        return DB::table($this->table)
            ->where($this->id, $value)
            ->doesntExist();
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Item already exists';
    }
}
