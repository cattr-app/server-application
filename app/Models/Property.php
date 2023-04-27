<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Property
 *
 * @property int $id
 * @property int $entity_id
 * @property string $entity_type
 * @property string $name
 * @property string $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @method static EloquentBuilder|Property newModelQuery()
 * @method static EloquentBuilder|Property newQuery()
 * @method static QueryBuilder|Property onlyTrashed()
 * @method static EloquentBuilder|Property query()
 * @method static EloquentBuilder|Property whereCreatedAt($value)
 * @method static EloquentBuilder|Property whereDeletedAt($value)
 * @method static EloquentBuilder|Property whereEntityId($value)
 * @method static EloquentBuilder|Property whereEntityType($value)
 * @method static EloquentBuilder|Property whereId($value)
 * @method static EloquentBuilder|Property whereName($value)
 * @method static EloquentBuilder|Property whereUpdatedAt($value)
 * @method static EloquentBuilder|Property whereValue($value)
 * @method static QueryBuilder|Property withTrashed()
 * @method static QueryBuilder|Property withoutTrashed()
 * @mixin EloquentIdeHelper
 * @property-read Model|EloquentIdeHelper $entity
 */
class Property extends Model
{
    use SoftDeletes;

    public const PROJECT_CODE = 'project';
    public const TASK_CODE = 'task';
    public const TIME_INTERVAL_CODE = 'time_interval';
    public const USER_CODE = 'user';
    public const TASK_COMMENT_CODE = 'task_comment';

    protected $table = 'properties';

    protected $fillable = [
        'entity_id',
        'entity_type',
        'name',
        'value',
    ];

    protected $casts = [
        'entity_id' => 'integer',
        'entity_type' => 'string',
        'name' => 'string',
        'value' => 'string',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public static function getTableName(): string
    {
        return with(new static())->getTable();
    }

    public static function loadMorphMap(): void
    {
        Relation::enforceMorphMap([
            self::PROJECT_CODE => Project::class,
            self::TASK_CODE => Task::class,
            self::TIME_INTERVAL_CODE => TimeInterval::class,
            self::USER_CODE => User::class,
            self::TASK_COMMENT_CODE => TaskComment::class,
        ]);
    }
}
