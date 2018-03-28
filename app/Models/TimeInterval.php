<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeInterval extends Model
{
    use SoftDeletes;

	/**
     * table name from database
     * @var string
     */
    protected $table = 'time_intervals';

    protected $fillable = array('task_id', 'start_at', 'end_at');

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }


    public function screenshots()
    {
    	return $this->hasMany(Screenshot::class, 'time_interval_id');
    }

}
