<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * App\Models\ProjectReport
 *
 * @property int $user_id
 * @property string $user_name
 * @property int $task_id
 * @property int $project_id
 * @property string $task_name
 * @property string $project_name
 * @property string $date
 * @property float|null $duration
 * @method static EloquentBuilder|ProjectReport whereDate($value)
 * @method static EloquentBuilder|ProjectReport whereDuration($value)
 * @method static EloquentBuilder|ProjectReport whereProjectId($value)
 * @method static EloquentBuilder|ProjectReport whereProjectName($value)
 * @method static EloquentBuilder|ProjectReport whereTaskId($value)
 * @method static EloquentBuilder|ProjectReport whereTaskName($value)
 * @method static EloquentBuilder|ProjectReport whereUserId($value)
 * @method static EloquentBuilder|ProjectReport whereUserName($value)
 * @mixin Eloquent
 */
class ProjectReport extends AbstractModel
{
    /**
     * @var string
     */
    protected $table = 'project_report';
}
