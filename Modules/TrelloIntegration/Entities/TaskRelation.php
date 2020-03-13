<?php

namespace Modules\TrelloIntegration\Entities;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;

class TaskRelation extends Model
{
    protected $table = 'trello_tasks_relation';

    protected $fillable = [
        'id',
        'task_id',
    ];

    protected $keyType = 'string';

    public $timestamps = false;

    // Connection with the App\Models\Task
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }
}
