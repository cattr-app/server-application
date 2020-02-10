<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Class TaskComment
 *
 * @property int $id
 * @property int $task_id
 * @property string $content
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property Task $task
 * @property User $user
 * @property int $user_id
 * @property-read Collection|Property[] $properties
 * @method static bool|null forceDelete()
 * @method static QueryBuilder|TaskComment onlyTrashed()
 * @method static bool|null restore()
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
class TaskComment extends AbstractModel
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

    /**
     * @return HasMany
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'entity_id')
            ->where('entity_type', Property::TASK_COMMENT_CODE);
    }
}
