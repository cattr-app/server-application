<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

/**
 * Class TaskComment
 *
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property string $content
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection|Property[] $properties
 * @property-read int|null $properties_count
 * @property-read Task $task
 * @property-read User $user
 * @method static EloquentBuilder|TaskComment newModelQuery()
 * @method static EloquentBuilder|TaskComment newQuery()
 * @method static QueryBuilder|TaskComment onlyTrashed()
 * @method static EloquentBuilder|TaskComment query()
 * @method static EloquentBuilder|TaskComment whereContent($value)
 * @method static EloquentBuilder|TaskComment whereCreatedAt($value)
 * @method static EloquentBuilder|TaskComment whereDeletedAt($value)
 * @method static EloquentBuilder|TaskComment whereId($value)
 * @method static EloquentBuilder|TaskComment whereTaskId($value)
 * @method static EloquentBuilder|TaskComment whereUpdatedAt($value)
 * @method static EloquentBuilder|TaskComment whereUserId($value)
 * @method static QueryBuilder|TaskComment withTrashed()
 * @method static QueryBuilder|TaskComment withoutTrashed()
 * @mixin EloquentIdeHelper
 */
class TaskComment extends Model
{
    use SoftDeletes;

    /**
     * table name from database
     * @var string
     */
    protected $table = 'task_comment';

    /**
     * @var array
     */
    protected $fillable = [
        'task_id',
        'user_id',
        'content',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'task_id' => 'integer',
        'user_id' => 'integer',
        'content' => 'string',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function properties(): MorphMany
    {
        return $this->morphMany(Property::class, 'entity');
    }
}
