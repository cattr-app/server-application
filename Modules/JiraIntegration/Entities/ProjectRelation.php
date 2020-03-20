<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 *
 * @property Project $project
 */
class ProjectRelation extends Model
{
    public $timestamps = false;

    protected $table = 'jira_projects_relation';

    protected $fillable = [
        'id',
        'project_id',
    ];

    protected $casts = [
        'project_id' => 'integer',
    ];


    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
