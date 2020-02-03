<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

class ProjectRelation extends Model
{
    protected $table = 'jira_projects_relation';

    protected $fillable = [
        'id',
        'project_id',
    ];

    protected $casts = [
        'project_id' => 'integer',
    ];

    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
