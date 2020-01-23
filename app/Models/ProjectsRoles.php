<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ProjectsRoles
 *
 * @package App\Models
 * @property int $project_id
 * @property int $role_id
 * @property string $created_at
 * @property string $updated_at
 * @property Role $role
 * @property Project $project
 * @method static EloquentBuilder|ProjectsRoles whereCreatedAt($value)
 * @method static EloquentBuilder|ProjectsRoles whereProjectId($value)
 * @method static EloquentBuilder|ProjectsRoles whereRoleId($value)
 * @method static EloquentBuilder|ProjectsRoles whereUpdatedAt($value)
 * @mixin Model
 */
class ProjectsRoles extends AbstractModel
{
    /**
     * table name from database
     * @var string
     */
    protected $table = 'projects_roles';

    /**
     * @var array
     */
    protected $fillable = [
        'project_id',
        'role_id',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'project_id' => 'integer',
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
     * @param EloquentBuilder $query
     * @return EloquentBuilder
     */
    protected function setKeysForSaveQuery(EloquentBuilder $query): EloquentBuilder
    {
        return $query->where('project_id', '=', $this->getAttribute('project_id'))
            ->where('role_id', '=', $this->getAttribute('role_id'));
    }

    /**
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * @return BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
