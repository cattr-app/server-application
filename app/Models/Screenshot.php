<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Screenshot extends Model
{
    use SoftDeletes;

    /**
     * table name from database
     * @var string
     */
    protected $table = 'screenshots';

    protected $fillable = array('time_interval_id', 'name', 'path');

    public function timeInverval()
    {
        return $this->belongsTo(TimeInverval::class, 'time_interval_id');
    }
    
}
