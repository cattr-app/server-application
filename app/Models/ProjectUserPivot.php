<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * App\Models\ProjectUserPivot
 *
 * @method static Builder|ProjectUserPivot newModelQuery()
 * @method static Builder|ProjectUserPivot newQuery()
 * @method static Builder|ProjectUserPivot query()
 * @mixin Eloquent
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
