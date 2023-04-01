<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\TaskHistory
 *
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property string $field
 * @property string $new_value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Task $task
 * @property-read User $user
 * @method static Builder|TaskHistory newModelQuery()
 * @method static Builder|TaskHistory newQuery()
 * @method static Builder|TaskHistory query()
 * @method static Builder|TaskHistory whereCreatedAt($value)
 * @method static Builder|TaskHistory whereField($value)
 * @method static Builder|TaskHistory whereId($value)
 * @method static Builder|TaskHistory whereNewValue($value)
 * @method static Builder|TaskHistory whereTaskId($value)
 * @method static Builder|TaskHistory whereUpdatedAt($value)
 * @method static Builder|TaskHistory whereUserId($value)
 * @mixin Eloquent
 * @property string|null $old_value
 * @method static Builder|TaskHistory whereOldValue($value)
 */
class TaskHistory extends Model
{
    /**
     * @var string
     */
    protected $table = 'task_history';

    /**
     * @var array
     */
    protected $fillable = [
        'task_id',
        'user_id',
        'field',
        'old_value',
        'new_value',
    ];

    /**
     * @var array
     */
    protected $casts
        = [
            'task_id' => 'integer',
            'user_id' => 'integer',
            'field' => 'string',
        ];

    /**
     * @var array
     */
    protected $dates
        = [
            'created_at',
            'updated_at',
        ];

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return with(new static())->getTable();
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
