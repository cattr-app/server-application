<?php

namespace Modules\GitlabIntegration\Entities;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskRelation extends Model
{
    public $timestamps = false;

    protected $table = 'gitlab_tasks_relations';

    protected $fillable = [
        'gitlab_id',
        'task_id',
        'gitlab_issue_iid',
    ];

    protected $primaryKey = 'gitlab_id';

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }
}
