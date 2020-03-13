<?php

namespace Modules\GitlabIntegration\Entities;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

class ProjectRelation extends Model
{
    // Table that stores the data
    protected $table = 'gitlab_projects_relations';

    // Fields that can be filled in while creating the model
    protected $fillable = [
        'gitlab_id',
        'project_id',
    ];

    protected $primaryKey = 'gitlab_id';

    // Turns off the default created_at and updated_at fields use
    public $timestamps = false;

    // Connection with the App\Models\Project
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
