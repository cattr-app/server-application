<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatusPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        return $user->isAdmin();
    }

    public function view(): bool
    {
        return true;
    }

    public function viewAny(): bool
    {
        return true;
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
