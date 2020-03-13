<?php

namespace Modules\GitlabIntegration\Entities;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;

class TaskRelation extends Model
{
    protected $table = 'gitlab_tasks_relations';

    protected $fillable = [
        'gitlab_id',
        'task_id',
        'gitlab_issue_iid',
    ];

    public $timestamps = false;

    protected $primaryKey = 'gitlab_id';

    // Connection with the App\Models\Task
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }
}
