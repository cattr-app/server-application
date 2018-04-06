<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Task
 * @package App\Models
 *
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property int $assigned_by
 * @property string $task_name
 * @property string $description
 * @property bool $active
 * @property string $url
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class Task extends Model
{
    use SoftDeletes;

	/**
     * table name from database
     * @var string
     */
    protected $table = 'tasks';

    /**
     * @var array
     */
    protected $fillable = ['project_id', 'task_name', 'description', 'active', 'user_id', 'assigned_by', 'url'];

    /**
     * @return BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo
     */
    public function assigned(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * @return HasMany
     */
    public function timeIntervals(): HasMany
    {
    	return $this->hasMany(TimeInterval::class, 'task_id');
    }

    public static function getTableName()
    {
        return with(new static)->getTable();
    }

}
