<?php

namespace App\Services;

use App\Models\Invitation;
use Webpatser\Uuid\Uuid;

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
     * @throws \Exception
     */
    public function create(array $user): ?Invitation
    {
        $invitation = Invitation::create([
            'email' => $user['email'],
            'key' => Uuid::generate(),
            'expires_at' => now()->addDays(self::EXPIRATION_TIME_IN_DAYS),
            'role_id' => $user['role_id']
        ]);

        return $invitation;
    }

    /**
     * Update invitation.
     *
     * @param int $id
     * @return Invitation|null
     * @throws \Exception
     */
    public function update(int $id): ?Invitation
    {
        $invitation = tap(Invitation::find($id))->update([
            'key' => Uuid::generate(),
            'expires_at' => now()->addDays(self::EXPIRATION_TIME_IN_DAYS)
        ]);
        
        return $invitation;
    }
}
