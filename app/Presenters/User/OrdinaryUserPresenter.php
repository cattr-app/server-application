<?php

namespace App\Presenters\User;

/**
 * Class OrdinaryUserPresenter
 * @package App\Presenters\User
 */
class OrdinaryUserPresenter extends UserPresenter
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $fillable = [
        'full_name',
        'email',
        'password',
        'user_language',
    ];

    /**
     * Get the fillable attributes for the model.
     *
     * @return array
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }
}
