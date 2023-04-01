<?php

namespace App\Models;

use Cache;
use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Role
 *
 * @todo Find a way to delete this module. Currently used in many migrations =(
 *
 * @deprecated Since 4.0.0, but still can be used in some old migrations
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
    use SoftDeletes, HasFactory;

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

    public static function getIdByName(string $name): ?int
    {
        return Cache::store('octane')->rememberForever(
            "role_id.$name",
            static fn() => optional(self::firstWhere('name', $name))->id,
        );
    }
}
