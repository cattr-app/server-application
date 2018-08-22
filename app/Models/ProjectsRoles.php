<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ProjectsRoles
 * @package App\Models
 *
 * @property int    $project_id
 * @property int    $role_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Role $role
 * @property Project $project
 */
class ProjectsRoles extends Model
{
    /**
     * table name from database
     * @var string
     */
    protected $table = 'projects_roles';

    protected function setKeysForSaveQuery(Builder $query)
    {
        $query
            ->where('project_id', '=', $this->getAttribute('project_id'))
            ->where('role_id', '=', $this->getAttribute('role_id'));
        return $query;
    }

    /**
     * @var array
     */
    protected $fillable = ['project_id','role_id'];

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
