<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

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
