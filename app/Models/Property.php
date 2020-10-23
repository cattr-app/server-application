<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * App\Models\Property
 *
 * @property int $id
 * @property int $entity_id
 * @property string $entity_type
 * @property string $name
 * @property string $value
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @method static bool|null forceDelete()
 * @method static bool|null restore()
 * @method static EloquentBuilder|Property whereCreatedAt($value)
 * @method static EloquentBuilder|Property whereDeletedAt($value)
 * @method static EloquentBuilder|Property whereEntityId($value)
 * @method static EloquentBuilder|Property whereEntityType($value)
 * @method static EloquentBuilder|Property whereId($value)
 * @method static EloquentBuilder|Property whereName($value)
 * @method static EloquentBuilder|Property whereUpdatedAt($value)
 * @method static EloquentBuilder|Property whereValue($value)
 * @method static EloquentBuilder|Property newModelQuery()
 * @method static EloquentBuilder|Property newQuery()
 * @method static EloquentBuilder|Property query()
 * @method static QueryBuilder|Property withTrashed()
 * @method static QueryBuilder|Property withoutTrashed()
 * @method static QueryBuilder|Property onlyTrashed()
 * @mixin EloquentIdeHelper
 */
class Property extends Model
{
    use SoftDeletes;

    public const APP_CODE = 'app';
    public const COMPANY_CODE = 'company';
    public const PROJECT_CODE = 'project';
    public const TASK_CODE = 'task';
    public const TIME_INTERVAL_CODE = 'time_interval';
    public const USER_CODE = 'user';
    public const TASK_COMMENT_CODE = 'task_comment';

    /**
     * @var string
     */
    protected $table = 'properties';

    /**
     * @var array
     */
    protected $fillable = [
        'entity_id',
        'entity_type',
        'name',
        'value',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'entity_id' => 'integer',
        'entity_type' => 'string',
        'name' => 'string',
        'value' => 'string',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public static function getTableName(): string
    {
        return with(new static())->getTable();
    }

    /**
     * Get property from database
     * @param string $scope
     * @param string $key
     * @param array $parameters
     * @return Collection
     */
    public static function getProperty(string $scope, string $key, array $parameters = []): Collection
    {
        // Making data for where query
        $queryData = [
            'entity_type' => $scope,
            'name' => $key
        ];

        if (!empty($parameters)) {
            $queryData = array_merge($queryData, $parameters);
        }

        return self::where($queryData)->get();
    }
}
