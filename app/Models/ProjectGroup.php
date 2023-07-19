<?php

namespace App\Models;

use App\Scopes\ProjectAccessScope;
use App\Traits\ExposePermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\AncestorsRelation;
use Kalnoy\Nestedset\DescendantsRelation;
use Kalnoy\Nestedset\NodeTrait;
use Kalnoy\Nestedset\QueryBuilder;

/**
 * @method static QueryBuilder withDepth()
 */
class ProjectGroup extends Model
{
    use SoftDeletes;
    use HasFactory;
    use ExposePermissions;
    use NodeTrait;

    protected $fillable = [
        'name',
    ];

    protected $dates = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected const PERMISSIONS = ['update', 'destroy'];

    public function groupParentsWithProjectsCount(): AncestorsRelation
    {
        return $this->ancestors()->withCount('projects')->defaultOrder();
    }

    public function groupParent(): HasOne
    {
        return $this->hasOne(ProjectGroup::class, 'id', 'parent_id');
    }

    public function descendantsWithDepthAndProjectsCount(): DescendantsRelation
    {
        return $this->descendants()->withCount('projects')->withDepth()->defaultOrder();
    }

    protected static function boot(): void
    {
        parent::boot();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'group');
    }
}
