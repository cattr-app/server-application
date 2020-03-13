<?php

namespace Modules\TrelloIntegration\Entities;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

class ProjectRelation extends Model
{
    // Table that stores the data
    protected $table = 'trello_projects_relation';

    // Fields that can be filled in while creating the model
    protected $fillable = [
        'id',
        'project_id',
    ];

    // Turns off the default created_at and updated_at fields use
    public $timestamps = false;

    protected $keyType = 'string';


    // Connection with the App\Models\Project
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
