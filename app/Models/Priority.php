<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Priority
 * @package App\Models
 */
class Priority extends AbstractModel
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

    /**
     * @return HasMany
     */
    public function tasks(): HasMany
    {
    	return $this->hasMany(Task::class, 'priority_id');
    }

    public static function getTableName()
    {
        return with(new static)->getTable();
    }
}
