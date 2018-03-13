<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use SoftDeletes;

    const PROJECT_CODE = 'project';
    const TASK_CODE = 'task';
    const TIME_INTERVAL_CODE = 'time_interval';
    const SCREENSHOT_CODE = 'screenshot';
    const USER_CODE = 'user';

    protected $table = 'properties';
    protected $fillable = array('entity_id', 'entity_type', 'name', 'value');
}
