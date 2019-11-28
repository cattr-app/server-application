<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Task
 * @package App\Models
 *
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property int $assigned_by
 * @property int $priority_id
 * @property string $task_name
 * @property string $description
 * @property int $active
 * @property string $url
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property bool $important
 *
 * @property TimeInterval[] $timeIntervals
 * @property User[] $user
 * @property User[] $assigned
 * @property Project[] $project
 * @property Priority $priority
 */
class Task extends AbstractModel
{
    use SoftDeletes;

    /**
     * table name from database
     * @var string
     */
    protected $table = 'tasks';

    /**
     * @var array
     */
    protected $fillable = [
        'project_id',
        'task_name',
        'description',
        'active',
        'user_id',
        'assigned_by',
        'url',
        'priority_id',
        'important',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'project_id' => 'integer',
        'task_name' => 'string',
        'description' => 'string',
        'active' => 'boolean',
        'user_id' => 'integer',
        'assigned_by' => 'integer',
        'url' => 'string',
        'priority_id' => 'integer',
        'important' => 'boolean',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public static function getTableName()
    {
        return with(new static)->getTable();
    }

    /**
     * Override parent boot and Call deleting event
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($tasks) {
            /** @var Task $tasks */
            foreach ($tasks->timeIntervals()->get() as $val) {
                $val->delete();
            }
        });
    }

    /**
     * @return HasMany
     */
    public function timeIntervals(): HasMany
    {
        return $this->hasMany(TimeInterval::class, 'task_id');
    }

    /**
     * @return BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

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
    public function assigned(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * @return BelongsTo
     */
    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

}
