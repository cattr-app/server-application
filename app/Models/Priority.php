<?php

namespace App\Models;

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
