<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $task_id
 *
 * @property Task $task
 */
class TaskRelation extends Model
{
    protected $table = 'jira_tasks_relation';

    protected $fillable = [
        'id',
        'task_id',
    ];

    protected $casts = [
        'task_id' => 'integer',
    ];

    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }
}
