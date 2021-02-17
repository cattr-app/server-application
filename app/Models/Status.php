<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Status
 *
 * @property int $id
 * @property string $name
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $tasks_count
 * @property-read Collection|Task[] $tasks
 * @method static EloquentBuilder|Status whereCreatedAt($value)
 * @method static EloquentBuilder|Status whereUpdatedAt($value)
 * @method static EloquentBuilder|Status whereId($value)
 * @method static EloquentBuilder|Status whereName($value)
 * @method static EloquentBuilder|Status whereActive($value)
 * @method static EloquentBuilder|Status newModelQuery()
 * @method static EloquentBuilder|Status newQuery()
 * @method static EloquentBuilder|Status query()
 * @mixin EloquentIdeHelper
 * @property-read int|null $tasks_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Status newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Status newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Status query()
 */
class Status extends Model
{
    /**
     * table name from database
     * @var string
     */
    protected $table = 'statuses';

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'active',
        'color',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'active' => 'boolean',
        'color' => 'string',
    ];

    public static function getTableName(): string
    {
        return with(new static())->getTable();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'status_id');
    }

    /**
     * @return BelongsToMany
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'projects_statuses', 'status_id', 'project_id')->withPivot('color');
    }
}
