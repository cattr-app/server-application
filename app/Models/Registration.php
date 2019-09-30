<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends AbstractModel
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = [
        'key',
        'email',
        'expires_at',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'key' => 'string',
        'email' => 'string',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'expires_at',
    ];
}
