<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * App\Models\ProjectUserPivot
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectUserPivot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectUserPivot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProjectUserPivot query()
 * @mixin \Eloquent
 */
class ProjectUserPivot extends Pivot
{
    /**
     * @var array
     */
    protected $casts = [
        'project_id' => 'int',
        'user_id' => 'int',
        'role_id' => 'int',
    ];
}
