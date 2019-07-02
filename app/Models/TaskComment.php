<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TaskComment
 * @package App\Models
 *
 * @property int $id
 * @property int $task_id
 * @property string $content
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 *
 * @property Task $task
 * @property User $user
 */
class TaskComment extends Model
{
    use SoftDeletes;

	/**
     * table name from database
     * @var string
     */
    protected $table = 'task_comment';

    /**
     * @var array
     */
    protected $fillable = ['task_id', 'content'];


    /**
     * @return BelongsTo
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'entity_id')->where('entity_type', '=', Property::TASK_COMMENT_CODE);
    }
}
