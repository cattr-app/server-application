<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskUsers extends Model
{
    /**
     * table name from database
     *
     * @var string
     */
    protected $table = 'tasks_users';

    /**
     * @var array
     */
    protected $fillable = [
        'task_id',
        'user_id',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'task_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    protected function setKeysForSaveQuery(Builder $query): Builder
    {
        return $query->where('task_id', '=', $this->getAttribute('task_id'))
            ->where('user_id', '=', $this->getAttribute('user_id'));
    }
}
