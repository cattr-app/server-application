<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;


/**
 * Class Token
 * @package App
 *
 * @property int $id
 * @property string $user_id
 * @property string $token
 * @property string $expires_at
 */
class Token extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'token',
        'expires_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
