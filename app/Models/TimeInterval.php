<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeInterval extends Model
{
    
	/**
     * table name from database
     * @var string
     */
    protected $table = 'time_intervals';



    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }


    public function screenshots()
    {
    	$this->hasMany(Screenshot::class, 'time_interval_id');
    }
    
}
