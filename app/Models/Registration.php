<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string $email
 * @property Carbon $expires_at
 * @method static EloquentBuilder|Registration whereEmail($value)
 * @method static EloquentBuilder|Registration whereExpiresAt($value)
 * @method static EloquentBuilder|Registration whereId($value)
 * @method static EloquentBuilder|Registration whereKey($value)
 * @mixin EloquentIdeHelper
 */
class Registration extends Model
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
