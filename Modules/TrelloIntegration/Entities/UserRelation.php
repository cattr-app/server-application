<?php

namespace Modules\TrelloIntegration\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRelation extends Model
{
    protected $table = 'trello_users_relation';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
    ];

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
