<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
