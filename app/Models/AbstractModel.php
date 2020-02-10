<?php
namespace App\Models;

use Fico7489\Laravel\EloquentJoin\EloquentJoinBuilder;
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AbstractModel
*/
abstract class AbstractModel extends Model
{
    use EloquentJoin;
    use DateTrait;

    /**
     * @return Builder|EloquentJoinBuilder
     */
    public static function joinQuery()
    {
        return static::query();
    }
}
