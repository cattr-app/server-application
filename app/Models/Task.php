<?php

namespace App\Models;

use App\User;
use Eloquent;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @apiDefine TaskObject
 *
 * @apiSuccess {Object}   task                Task entity
 * @apiSuccess {Integer}  task.id             ID
 * @apiSuccess {Integer}  task.project_id     The ID of the linked project
 * @apiSuccess {Integer}  task.user_id        The ID of the linked user
 * @apiSuccess {Integer}  task.assigned_by    The ID of the user that assigned task
 * @apiSuccess {Integer}  task.priority_id    The ID of the priority
 * @apiSuccess {String}   task.task_name      Name of the task
 * @apiSuccess {String}   task.description    Description of the task
 * @apiSuccess {Boolean}  task.active         Indicates active task when `TRUE`
 * @apiSuccess {String}   task.important      Indicates important task when `TRUE`
 * @apiSuccess {String}   task.created_at     Creation DateTime
 * @apiSuccess {String}   task.updated_at     Update DateTime
 * @apiSuccess {String}   task.deleted_at     Delete DateTime or `NULL` if user wasn't deleted
 * @apiSuccess {Array}    task.timeIntervals  Time intervals of the task
 * @apiSuccess {Array}    task.user           Linked users
 * @apiSuccess {Array}    task.assigned       Users, that assigned this task
 * @apiSuccess {Array}    task.project        The project that task belongs to
 * @apiSuccess {Object}   task.priority       Task priority
 */

/**
 * Class Task
 *
 * @package App\Models
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
 * @property TimeInterval[] $timeIntervals
 * @property User[]         $user
 * @property User[]         $assigned
 * @property Project[]      $project
 * @property Priority       $priority
 * @method static bool|null forceDelete()
 * @method static QueryBuilder|Task onlyTrashed()
 * @method static bool|null restore()
 * @method static EloquentBuilder|Task whereActive($value)
 * @method static EloquentBuilder|Task whereAssignedBy($value)
 * @method static EloquentBuilder|Task whereCreatedAt($value)
 * @method static EloquentBuilder|Task whereDeletedAt($value)
 * @method static EloquentBuilder|Task whereDescription($value)
 * @method static EloquentBuilder|Task whereId($value)
 * @method static EloquentBuilder|Task whereImportant($value)
 * @method static EloquentBuilder|Task wherePriorityId($value)
 * @method static EloquentBuilder|Task whereProjectId($value)
 * @method static EloquentBuilder|Task whereTaskName($value)
 * @method static EloquentBuilder|Task whereUpdatedAt($value)
 * @method static EloquentBuilder|Task whereUrl($value)
 * @method static EloquentBuilder|Task whereUserId($value)
 * @method static QueryBuilder|Task withTrashed()
 * @method static QueryBuilder|Task withoutTrashed()
 * @mixin Eloquent
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
        'active' => 'integer',
        'user_id' => 'integer',
        'assigned_by' => 'integer',
        'url' => 'string',
        'priority_id' => 'integer',
        'important' => 'integer',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Override parent boot and Call deleting event
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function($tasks) {
            /** @var Task $tasks */
            foreach ($tasks->timeIntervals()->get() as $val) {
                $val->delete();
            }
        });
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
     * @return HasMany
     */
    public function timeIntervals(): HasMany
    {
    	return $this->hasMany(TimeInterval::class, 'task_id');
    }

    /**
     * @return BelongsTo
     */
    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return with(new static)->getTable();
    }

}
