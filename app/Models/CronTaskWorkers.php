<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class CronTaskWorkers
 *
 * @property int $user_id
 * @property int $task_id
 * @property int $duration
 * @property int $offset,
 * @property bool $created_by_cron
 * @method static EloquentBuilder|Task whereTaskId($value)
 * @mixin EloquentIdeHelper
 */
class CronTaskWorkers extends Model
{
    public $table = "cron_task_workers";

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = [
        'user_id' => 'integer',
        'task_id' => 'integer',
        'duration' => 'integer',
        'offset' => 'integer',
        'created_by_cron' => 'boolean'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'task_id' => 'integer',
        'duration' => 'integer',
        'offset' => 'integer',
        'created_by_cron' => 'boolean'
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id')->withoutGlobalScopes();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScopes();
    }
}
