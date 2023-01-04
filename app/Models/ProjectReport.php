<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ProjectReport
 *
 * @deprecated 
 * @property int $user_id
 * @property string $user_name
 * @property int $task_id
 * @property int $project_id
 * @property string $task_name
 * @property string $project_name
 * @property string $date
 * @property string|null $duration
 * @property-read Project $project
 * @property-read Task $task
 * @property-read User $user
 * @method static Builder|ProjectReport newModelQuery()
 * @method static Builder|ProjectReport newQuery()
 * @method static Builder|ProjectReport query()
 * @method static Builder|ProjectReport whereDate($value)
 * @method static Builder|ProjectReport whereDuration($value)
 * @method static Builder|ProjectReport whereProjectId($value)
 * @method static Builder|ProjectReport whereProjectName($value)
 * @method static Builder|ProjectReport whereTaskId($value)
 * @method static Builder|ProjectReport whereTaskName($value)
 * @method static Builder|ProjectReport whereUserId($value)
 * @method static Builder|ProjectReport whereUserName($value)
 * @mixin EloquentIdeHelper
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
