<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Screenshot extends Model
{

    /**
     * table name from database
     * @var string
     */
    protected $table = 'screenshots';


    public function timeInverval()
    {
        return $this->belongsTo(TimeInverval::class, 'time_interval_id');
    }
    
}
