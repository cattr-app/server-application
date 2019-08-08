<?php

namespace Modules\GitLabIntegration\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GitLabProject extends Model
{
    /**
     * @var string
     */
    protected $table = 'gitlab_projects';

    /**
     * @var array
     */
    protected $fillable = [
        'gitlab_url',
        'gitlab_project_id',
        'name',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'gitlab_projects_users', 'project_id', 'user_id');
    }
}
