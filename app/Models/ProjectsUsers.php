<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\ProjectsUsers
 *
 * @property int $project_id
 * @property int $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $role_id
 * @property-read Project $project
 * @property-read User $user
 * @method static EloquentBuilder|ProjectsUsers newModelQuery()
 * @method static EloquentBuilder|ProjectsUsers newQuery()
 * @method static EloquentBuilder|ProjectsUsers query()
 * @method static EloquentBuilder|ProjectsUsers whereCreatedAt($value)
 * @method static EloquentBuilder|ProjectsUsers whereProjectId($value)
 * @method static EloquentBuilder|ProjectsUsers whereRoleId($value)
 * @method static EloquentBuilder|ProjectsUsers whereUpdatedAt($value)
 * @method static EloquentBuilder|ProjectsUsers whereUserId($value)
 * @mixin EloquentIdeHelper
 */
class ProjectsUsers extends Model
{
    /**
     * table name from database
     *
     * @var string
     */
    protected $table = 'projects_users';

    /**
     * @var array
     */
    protected $fillable = [
        'project_id',
        'user_id',
        'role_id',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'project_id' => 'integer',
        'user_id' => 'integer',
        'role_id' => 'integer',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    protected function setKeysForSaveQuery($query): EloquentBuilder
    {
        $query->where('project_id', '=', $this->getAttribute('project_id'))
            ->where('user_id', '=', $this->getAttribute('user_id'));

        return $query;
    }
}
