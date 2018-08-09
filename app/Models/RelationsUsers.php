<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * Class RelationsUsers
 * @package App\Models
 *
 * @property int    $attached_user_id
 * @property int    $user_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $user
 * @property User $attached_user
 */
class RelationsUsers extends Model
{
    /**
     * table name from database
     * @var string
     */
    protected $table = 'relations_users';

    protected function setKeysForSaveQuery(Builder $query)
    {
        $query
            ->where('attached_user_id', '=', $this->getAttribute('attached_user_id'))
            ->where('user_id', '=', $this->getAttribute('user_id'));
        return $query;
    }

    /**
     * @var array
     */
    protected $fillable = ['attached_user_id','user_id'];

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
    public function attached_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attached_user_id');
    }
}
