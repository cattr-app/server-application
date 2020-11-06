<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @apiDefine InvitationObject
 *
 * @apiSuccess {Integer}  invitation.id          ID
 * @apiSuccess {String}   invitation.email       Email
 * @apiSuccess {String}   invitation.key         Unique invitation token
 * @apiSuccess {String}   invitation.expires_at  The token expiration time
 * @apiSuccess {String}   invitation.role_id     ID of the role that will be assigned to the created user
 *
 */

/**
 * App\Models\Invitation
 *
 * @property int $id
 * @property string $key
 * @property string $email
 * @property Carbon $expires_at
 * @method static EloquentBuilder|Invitation whereEmail($value)
 * @method static EloquentBuilder|Invitation whereExpiresAt($value)
 * @method static EloquentBuilder|Invitation whereId($value)
 * @method static EloquentBuilder|Invitation whereKey($value)
 * @method static EloquentBuilder|Invitation whereRoleId($value)
 * @mixin EloquentIdeHelper
 * @property int|null $role_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Invitation query()
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
}
