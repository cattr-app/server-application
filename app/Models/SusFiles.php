<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Eloquent as EloquentIdeHelper;

/**
 * App\Models\SusFiles
 *
 * @property string $id
 * @property string $path
 * @property string $mime_type
 * @property string $hash
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static EloquentBuilder|Attachment newModelQuery()
 * @method static EloquentBuilder|Attachment newQuery()
 * @method static EloquentBuilder|Attachment query()
 * @method static EloquentBuilder|Attachment whereCreatedAt($value)
 * @method static EloquentBuilder|Attachment whereHash($value)
 * @method static EloquentBuilder|Attachment whereId($value)
 * @method static EloquentBuilder|Attachment whereMimeType($value)
 * @method static EloquentBuilder|Attachment whereUpdatedAt($value)
 * @mixin EloquentIdeHelper
 */
class SusFiles extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'path',
        'mime_type',
        'hash',
    ];

    protected $casts = [
        'path' => 'string',
        'mime_type' => 'string',
        'hash' => 'string',
    ];
}
