<?php

namespace App\Models;

use App\Scopes\TaskAccessScope;
use App\Traits\ExposePermissions;
use Database\Factories\TaskFactory;
use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Parsedown;

/**
 * Class Task
 *
 * @property int $id
 * @property int $project_id
 * @property string $task_name
 * @property string|null $description
 * @property int $assigned_by
 * @property string|null $url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int $priority_id
 * @property int $important
 * @property int|null $status_id
 * @property float $relative_position
 * @property Carbon|null $due_date
 * @property-read User $assigned
 * @property-read Collection|TaskHistory[] $changes
 * @property-read int|null $changes_count
 * @property-read Collection|TaskComment[] $comments
 * @property-read int|null $comments_count
 * @property-read array $can
 * @property-read Priority $priority
 * @property-read Project $project
 * @property-read Status|null $status
 * @property-read Collection|TimeInterval[] $timeIntervals
 * @property-read int|null $time_intervals_count
 * @property-read Collection|User[] $users
 * @property-read int|null $users_count
 * @method static TaskFactory factory(...$parameters)
 * @method static EloquentBuilder|Task newModelQuery()
 * @method static EloquentBuilder|Task newQuery()
 * @method static QueryBuilder|Task onlyTrashed()
 * @method static EloquentBuilder|Task query()
 * @method static EloquentBuilder|Task whereAssignedBy($value)
 * @method static EloquentBuilder|Task whereCreatedAt($value)
 * @method static EloquentBuilder|Task whereDeletedAt($value)
 * @method static EloquentBuilder|Task whereDescription($value)
 * @method static EloquentBuilder|Task whereDueDate($value)
 * @method static EloquentBuilder|Task whereId($value)
 * @method static EloquentBuilder|Task whereImportant($value)
 * @method static EloquentBuilder|Task wherePriorityId($value)
 * @method static EloquentBuilder|Task whereProjectId($value)
 * @method static EloquentBuilder|Task whereRelativePosition($value)
 * @method static EloquentBuilder|Task whereStatusId($value)
 * @method static EloquentBuilder|Task whereTaskName($value)
 * @method static EloquentBuilder|Task whereUpdatedAt($value)
 * @method static EloquentBuilder|Task whereUrl($value)
 * @method static QueryBuilder|Task withTrashed()
 * @method static QueryBuilder|Task withoutTrashed()
 * @mixin EloquentIdeHelper
 * @property-read Collection|Property[] $properties
 * @property-read int|null $properties_count
 */
class Task extends Model
{
    use SoftDeletes;
    use ExposePermissions;
    use HasFactory;

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
        'assigned_by',
        'url',
        'priority_id',
        'status_id',
        'important',
        'relative_position',
        'due_date',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'project_id' => 'integer',
        'task_name' => 'string',
        'description' => 'string',
        'assigned_by' => 'integer',
        'url' => 'string',
        'priority_id' => 'integer',
        'status_id' => 'integer',
        'important' => 'integer',
        'relative_position' => 'float',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'due_date',
    ];

    protected const PERMISSIONS = ['update', 'destroy'];

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope(new TaskAccessScope);

        static::deleting(static function (Task $task) {
            $task->timeIntervals()->delete();
        });

        static::created(static function (Task $task) {
            dispatch(static function () use ($task) {
                foreach ($task->users as $user) {
                    $task->project->users()->firstOrCreate(
                        ['id' => $user->id],
                        ['role_id' => \App\Enums\Role::USER]
                    );
                }
            });
        });
    }

    public function timeIntervals(): HasMany
    {
        return $this->hasMany(TimeInterval::class, 'task_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tasks_users', 'task_id', 'user_id')->withoutGlobalScopes();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'task_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id')->withoutGlobalScopes();
    }

    public function assigned(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function getDescription(): string
    {
        return tap(new Parsedown())->text($this->description);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(TaskHistory::class, 'task_id');
    }

    public function properties(): MorphMany
    {
        return $this->morphMany(Property::class, 'entity');
    }
}
