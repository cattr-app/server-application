<?php

namespace App\Policies;

use App\Models\User;
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
        if ($user->hasRole('admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, User $model): bool
    {
        return cache()->remember(
            "role_user_user_{$user->id}_$model->id",
            config('cache.role_caching_ttl'),
            static fn() => User::whereId($model->id)->exists(),
        );
    }

    public function create(): bool
    {
        return false;
    }

    /**
     * @throws ValidationException
     */
    public function update(User $user, User $model): bool
    {
        $extraFields = array_diff(array_keys(request()->except('id')), self::ALLOWED_EDITABLE_FIELDS);

        if (count($extraFields)) {
            $errorMessages = [];

            foreach ($extraFields as $fieldKey) {
                $errorMessages[$fieldKey] = __('You don\'t have permission to edit this field');
            }

            throw ValidationException::withMessages($errorMessages);
        }

        return $user->id === $model->id;
    }

    public function destroy(): bool
    {
        return false;
    }

    public function sendInvite(): bool
    {
        return false;
    }
}
