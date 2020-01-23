<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * Class ProjectsUsers
 *
 * @package App\Models
 * @property int     $project_id
 * @property int     $user_id
 * @property int     $role_id
 * @property string  $created_at
 * @property string  $updated_at
 * @property User    $user
 * @property Project $project
 * @property Role    $role
 * @method static EloquentBuilder|ProjectsUsers whereCreatedAt($value)
 * @method static EloquentBuilder|ProjectsUsers whereProjectId($value)
 * @method static EloquentBuilder|ProjectsUsers whereUpdatedAt($value)
 * @method static EloquentBuilder|ProjectsUsers whereUserId($value)
 * @mixin EloquentIdeHelper
 */
class ProjectsUsers extends AbstractModel
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

    /**
     * @param  EloquentBuilder  $query
     *
     * @return EloquentBuilder
     */
    protected function setKeysForSaveQuery(Builder $query): EloquentBuilder
    {
        return $query->where('project_id', '=', $this->getAttribute('project_id'))
            ->where('user_id', '=', $this->getAttribute('user_id'));
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
