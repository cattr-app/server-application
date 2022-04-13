<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\Module
 *
 * @property int $id
 * @property string $name
 * @property bool $enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|Module newModelQuery()
 * @method static Builder|Module newQuery()
 * @method static Builder|Module query()
 * @method static Builder|Module whereCreatedAt($value)
 * @method static Builder|Module whereEnabled($value)
 * @method static Builder|Module whereId($value)
 * @method static Builder|Module whereName($value)
 * @method static Builder|Module whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Module extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['name', 'enabled'];

    /**
     * @var array
     */
    protected $casts = ['name' => 'string', 'enabled' => 'boolean'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * @var array
     */
    protected $visible = ['name', 'enabled'];
}
