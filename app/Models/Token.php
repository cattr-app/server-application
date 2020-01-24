<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * Class Token
 *
 * @package App
 * @property int $id
 * @property string $user_id
 * @property string $token
 * @property string $expires_at
 * @property-read User $user
 * @method static EloquentBuilder|Token whereExpiresAt($value)
 * @method static EloquentBuilder|Token whereId($value)
 * @method static EloquentBuilder|Token whereToken($value)
 * @method static EloquentBuilder|Token whereUserId($value)
 * @mixin EloquentIdeHelper
 */
class Token extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = [
        'token',
        'expires_at'
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
