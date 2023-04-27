<?php

namespace App\Services;

use App\Models\Invitation;
use Exception;
use Str;

class InvitationService
{
    /**
     * The invitation expires in one months.
     */
    protected const EXPIRATION_TIME_IN_DAYS = 30;

    /**
     * Create new invitation.
     *
     * @param array $user
     * @return Invitation|null
     * @throws Exception
     */
    public static function create(array $user): ?Invitation
    {
        return Invitation::create([
            'email' => $user['email'],
            'key' => Str::uuid(),
            'expires_at' => now()->addDays(self::EXPIRATION_TIME_IN_DAYS),
            'role_id' => $user['role_id']
        ]);
    }

    /**
     * Update invitation.
     *
     * @param int $id
     * @return Invitation|null
     * @throws Exception
     */
    public static function update(int $id): ?Invitation
    {
        return tap(Invitation::find($id))->update([
            'key' => Str::uuid(),
            'expires_at' => now()->addDays(self::EXPIRATION_TIME_IN_DAYS)
        ]);
    }
}
