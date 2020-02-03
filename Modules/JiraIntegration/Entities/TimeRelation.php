<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\TimeInterval;
use Illuminate\Database\Eloquent\Model;

class TimeRelation extends Model
{
    protected $table = 'jira_time_relation';

    protected $fillable = [
        'jira_task_id',
        'time_interval_id',
    ];

    protected $casts = [
        'jira_task_id' => 'integer',
        'time_interval_id' => 'integer',
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
}
