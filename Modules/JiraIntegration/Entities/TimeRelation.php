<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $jira_task_id
 * @property int $time_interval_id
 * @property int $user_id
 *
 * @property TaskRelation $taskRelation
 * @property TimeInterval $timeInterval
 * @property User $user
 */
class TimeRelation extends Model
{
    public $timestamps = false;

    protected $table = 'jira_time_relation';

    protected $fillable = [
        'jira_task_id',
        'time_interval_id',
        'user_id',
    ];

    protected $casts = [
        'jira_task_id' => 'integer',
        'time_interval_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function taskRelation(): BelongsTo
    {
        return $this->belongsTo(TaskRelation::class, 'jira_task_id', 'id');
    }

    public function timeInterval(): BelongsTo
    {
        return $this->belongsTo(TimeInterval::class, 'time_interval_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
