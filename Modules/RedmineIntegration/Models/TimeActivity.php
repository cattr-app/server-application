<?php
namespace Modules\RedmineIntegration\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;
use App\Task;
use App\TimeInterval;


/**
 * Class TimeActivity
 * @package App\Models
 *
 * @property datetime $last_time_activity
 * @property int $user_id
 * @property int $time_interval_id
 * @property int $task_id
 *
 * @property User $user
 * @property Task $task
 * @property TimeInterval $timeInterval
 */
class TimeActivity extends Model
{
    /**
     * table name from database
     * @var string
     */
    protected $table = 'user_time_activity';

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * @return BelongsTo
     */
    public function timeInterval(): BelongsTo
    {
        return $this->belongsTo(TimeInterval::class, 'time_interval_id');
    }

}
