<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Models\Attachment
 *
 * @property string $id
 * @property int $attachmentable_id
 * @property string $attachmentable_type
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property string $hash
 * @property int $project_id
 * @property int $user_id
 * @property boolean $healthy
 */

class Attachment extends Model
{
    use HasFactory;
    use HasUuids;
//    TODO: soft deletes?

    protected $fillable = [
        'attachmentable_id',
        'attachmentable_type',
        'original_name',
        'mime_type',
        'size',
        'hash',
        'project_id',
        'user_id',
        'healthy',
    ];

    protected $casts = [
        'attachmentable_id' => 'integer',
        'attachmentable_type' => 'string',
        'original_name' => 'string',
        'mime_type' => 'string',
        'size' => 'integer',
        'hash' => 'string',
        'project_id' => 'integer',
        'user_id' => 'integer',
        'healthy' => 'boolean',
    ];

    /**
     * It's important to name the relationship the same as the method because otherwise
     * eager loading of the polymorphic relationship will fail on queued jobs.
     *
     * @see https://github.com/laravelio/laravel.io/issues/350
     */
    public function attachmentAbleRelation(): MorphTo
    {
        return $this->morphTo('attachmentAbleRelation', 'attachmentable_type', 'attachmentable_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
