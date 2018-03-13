<?php

namespace Modules\RedmineIntegration\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Models\Project;

class RedmineProject extends Model
{
    protected $table = 'redmine_projects';

    protected $fillable = ['project_id', 'redmine_project_id'];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
