<?php

namespace App\Models;

use App\Events\InvitationCreated;
use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\Invitation
 *
 * @property int $id
 * @property string $key
 * @property string $email
 * @property Carbon $expires_at
 * @property int|null $role_id
 * @method static EloquentBuilder|Invitation newModelQuery()
 * @method static EloquentBuilder|Invitation newQuery()
 * @method static EloquentBuilder|Invitation query()
 * @method static EloquentBuilder|Invitation whereEmail($value)
 * @method static EloquentBuilder|Invitation whereExpiresAt($value)
 * @method static EloquentBuilder|Invitation whereId($value)
 * @method static EloquentBuilder|Invitation whereKey($value)
 * @method static EloquentBuilder|Invitation whereRoleId($value)
 * @mixin EloquentIdeHelper
 */

class Invitation extends Model
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
        'role_id',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'key' => 'string',
        'email' => 'string',
        'role_id' => 'int',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'expires_at',
    ];

    protected $dispatchesEvents = [
        'created' => InvitationCreated::class,
        'updated' => InvitationCreated::class,
    ];
}
