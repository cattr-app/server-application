<?php

namespace Modules\TrelloIntegration\Entities;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskRelation extends Model
{
    public $timestamps = false;

    protected $table = 'trello_tasks_relation';

    protected $fillable = [
        'id',
        'task_id',
    ];
    protected $keyType = 'string';

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }
}
