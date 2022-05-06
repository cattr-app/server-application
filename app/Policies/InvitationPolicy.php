<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\User;
use App\Models\Invitation;

class InvitationPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }
    }

    public function view(): bool
    {
        return false;
    }

    public function viewAny(): bool
    {
        return false;
    }

    public function create(): bool
    {
        return false;
    }

    public function update(): bool
    {
        return false;
    }

    public function destroy(): bool
    {
        return false;
    }
}
