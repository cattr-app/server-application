<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property-read Collection|Task[] $tasks
 * @method static EloquentBuilder|Priority whereId($value)
 * @method static EloquentBuilder|Priority whereName($value)
 * @mixin EloquentIdeHelper
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
