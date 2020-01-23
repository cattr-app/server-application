<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Class Property
 *
 * @package App\Models
 * @property int    $id
 * @property int    $entity_id
 * @property string $entity_type
 * @property string $name
 * @property string $value
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @method static bool|null forceDelete()
 * @method static QueryBuilder|Property onlyTrashed()
 * @method static bool|null restore()
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
 */
class Property extends AbstractModel
{
    use SoftDeletes;

    public const COMPANY_CODE = 'company';
    public const PROJECT_CODE = 'project';
    public const TASK_CODE = 'task';
    public const TIME_INTERVAL_CODE = 'time_interval';
    public const SCREENSHOT_CODE = 'screenshot';
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

    /**
     * @return string
     */
    public static function getTableName()
    {
        return with(new static)->getTable();
    }

    /**
     * Get property from database
     *
     * @param  string  $scope
     * @param  string  $key
     * @param  array   $parameters
     *
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
