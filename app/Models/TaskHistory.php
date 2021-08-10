<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    protected $casts = [
        'task_id' => 'integer',
        'user_id' => 'integer',
        'field' => 'string',
    ];

    /**
     * @var array
     */
    protected $dates = [
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
