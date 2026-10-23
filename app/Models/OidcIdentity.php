<?php

namespace App\Models;

use App\Scopes\UserAccessScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OidcIdentity extends Model
{
    protected $guarded = ['id'];

    public static function identityHash(string $issuer, string $subject): string
    {
        // Avoid case-insensitive MySQL collation changing the meaning of iss/sub.
        return hash('sha256', $issuer . "\0" . $subject);
    }

    public function user(): BelongsTo
    {
        // The account's soft-delete scope remains in force.
        return $this->belongsTo(User::class)->withoutGlobalScope(UserAccessScope::class);
    }
}
