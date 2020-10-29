<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ProjectReport
 *
 * @mixin EloquentIdeHelper
 * @property-read \App\Models\Project $project
 * @property-read \App\Models\Task $task
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectReport query()
 */
class ProjectReport extends Model
{
    protected $table = 'project_report';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
