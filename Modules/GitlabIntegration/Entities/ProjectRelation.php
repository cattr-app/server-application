<?php

namespace Modules\GitlabIntegration\Entities;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRelation extends Model
{
    // Table that stores the data
    public $timestamps = false;

    // Fields that can be filled in while creating the model
    protected $table = 'gitlab_projects_relations';

    protected $fillable = [
        'gitlab_id',
        'project_id',
    ];

    // Turns off the default created_at and updated_at fields use
    protected $primaryKey = 'gitlab_id';

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
