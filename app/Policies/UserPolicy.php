<?php

namespace App\Policies;

use App\Models\User;
use Cache;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Validation\ValidationException;

class UserPolicy
{
    use HandlesAuthorization;

    private const ALLOWED_EDITABLE_FIELDS = [
        'full_name',
        'email',
        'password',
        'user_language',
    ];

    public function before(User $user): ?bool
    {
        return $user->isAdmin() ?: null;
    }

    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, User $model): bool
    {
        return Cache::store('octane')->remember(
            "role_user_user_{$user->id}_$model->id",
            config('cache.role_caching_ttl'),
            static fn() => User::whereId($model->id)->exists(),
        );
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * @throws ValidationException
     */
    public function update(User $user, User $model): bool
    {
        $extraFields = array_diff(array_keys(request()?->except('id')), self::ALLOWED_EDITABLE_FIELDS);

        if (count($extraFields)) {
            $errorMessages = [];

            foreach ($extraFields as $fieldKey) {
                $errorMessages[$fieldKey] = __('You don\'t have permission to edit this field');
            }

            throw ValidationException::withMessages($errorMessages);
        }

        return $user->id === $model->id;
    }

    public function destroy(User $user): bool
    {
        return $user->isAdmin();
    }

    public function sendInvite(): bool
    {
        return false;
    }
}
