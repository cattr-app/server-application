<?php

namespace Modules\TrelloIntegration\Entities;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeRelation extends Model
{
    public $timestamps = false;

    protected $table = 'trello_time_relation';

    protected $fillable = [
        'trello_task_id',
        'time_interval_id',
        'user_id',
    ];

    protected $casts = [
        'trello_task_id' => 'string',
        'time_interval_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function taskRelation(): BelongsTo
    {
        return $this->belongsTo(TaskRelation::class, 'trello_task_id', 'id');
    }

    public function timeInterval(): BelongsTo
    {
        return $this->belongsTo(TimeInterval::class, 'time_interval_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
