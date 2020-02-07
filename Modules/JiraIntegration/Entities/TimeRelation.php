<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\{TimeInterval, User};
use Illuminate\Database\Eloquent\Model;

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
    protected $table = 'jira_time_relation';

    protected $fillable = [
        'jira_task_id',
        'time_interval_id',
        'user_id',
    ];

    protected $casts = [
        'jira_task_id'     => 'integer',
        'time_interval_id' => 'integer',
        'user_id'          => 'integer',
    ];

    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function taskRelation()
    {
        return $this->belongsTo(TaskRelation::class, 'jira_task_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function timeInterval()
    {
        return $this->belongsTo(TimeInterval::class, 'time_interval_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
