<?php

namespace App\Models;

use App\Exceptions\Entities\AuthorizationException;
use Eloquent as EloquentIdeHelper;
use Exception;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Auth;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * @apiDefine RoleObject
 *
 * @apiSuccess {Integer}  role.id          ID
 * @apiSuccess {Integer}  role.name        Name
 * @apiSuccess {ISO8601}  role.created_at  Creation DateTime
 * @apiSuccess {ISO8601}  role.updated_at  Update DateTime
 * @apiSuccess {ISO8601}  role.deleted_at  Delete DateTime or `NULL` if wasn't deleted
 *
 * @apiVersion 1.0.0
 */

/**
 * App\Models\Role
 *
 * @apiDefine RoleParams
 * @apiParam {Integer}  [id]          ID
 * @apiParam {Integer}  [name]        Name
 * @apiParam {ISO8601}  [created_at]  Creation DateTime
 * @apiParam {ISO8601}  [updated_at]  Update DateTime
 * @apiParam {ISO8601}  [deleted_at]  Delete DateTime
 * @apiParam {String}   [with]        For add relation model in response
 * @apiParam {Object}   [users]       Roles's relation users. All params in <a href="#api-User-GetUserList" >@User</a>
 * @apiParam {Object}   [rules]      Roles's relation rules. All params in<a href="#api-Rule-GetRulesActions">@Rules</a>
 * @apiVersion 1.0.0
 * @property int $id
 * @property string $name
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|Project[] $projects
 * @property-read int|null $projects_count
 * @property-read Collection|User[] $users
 * @property-read int|null $users_count
 * @method static EloquentBuilder|Role newModelQuery()
 * @method static EloquentBuilder|Role newQuery()
 * @method static QueryBuilder|Role onlyTrashed()
 * @method static EloquentBuilder|Role query()
 * @method static EloquentBuilder|Role whereCreatedAt($value)
 * @method static EloquentBuilder|Role whereDeletedAt($value)
 * @method static EloquentBuilder|Role whereId($value)
 * @method static EloquentBuilder|Role whereName($value)
 * @method static EloquentBuilder|Role whereUpdatedAt($value)
 * @method static QueryBuilder|Role withTrashed()
 * @method static QueryBuilder|Role withoutTrashed()
 * @mixin EloquentIdeHelper
 */

class Role extends Model
{
    use SoftDeletes;

    /**
     * table name from database
     *
     * @var string
     */
    protected $table = 'role';

    /**
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * @var array
     */
    protected $casts = [
        'name' => 'string'
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }

    /**
     * @return BelongsToMany
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'projects_roles', 'role_id', 'project_id');
    }
}
