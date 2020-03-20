<?php

namespace Modules\TrelloIntegration\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRelation extends Model
{
    public $timestamps = false;
    protected $table = 'trello_users_relation';
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
