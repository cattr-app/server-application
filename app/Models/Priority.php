<?php

namespace App\Models;

use Eloquent;
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
 * @property string|null $color
 * @property-read Collection|Task[] $tasks
 * @property-read int|null $tasks_count
 * @method static EloquentBuilder|Priority newModelQuery()
 * @method static EloquentBuilder|Priority newQuery()
 * @method static EloquentBuilder|Priority query()
 * @method static EloquentBuilder|Priority whereColor($value)
 * @method static EloquentBuilder|Priority whereCreatedAt($value)
 * @method static EloquentBuilder|Priority whereId($value)
 * @method static EloquentBuilder|Priority whereName($value)
 * @method static EloquentBuilder|Priority whereUpdatedAt($value)
 * @mixin Eloquent
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
        'color',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'color' => 'string',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'priority_id');
    }
}
