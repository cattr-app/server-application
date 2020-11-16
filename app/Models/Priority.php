<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Priority
 *
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $tasks_count
 * @property-read Collection|Task[] $tasks
 * @method static EloquentBuilder|Priority whereCreatedAt($value)
 * @method static EloquentBuilder|Priority whereUpdatedAt($value)
 * @method static EloquentBuilder|Priority whereId($value)
 * @method static EloquentBuilder|Priority whereName($value)
 * @method static EloquentBuilder|Priority newModelQuery()
 * @method static EloquentBuilder|Priority newQuery()
 * @method static EloquentBuilder|Priority query()
 * @mixin EloquentIdeHelper
 * @property-read int|null $tasks_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Priority newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Priority newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Priority query()
 */
class Priority extends Model
{
    /**
     * table name from database
     * @var string
     */
    protected $table = 'priorities';

    /**
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'name' => 'string',
    ];

    public static function getTableName(): string
    {
        return with(new static())->getTable();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'priority_id');
    }
}
