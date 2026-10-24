<?php

namespace App\Http\Requests\User;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\User;
use App\Scopes\UserAccessScope;
use App\Http\Requests\CattrFormRequest;

class ShowUserRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        $target = User::withoutGlobalScope(UserAccessScope::class)->find(request('id'));

        return $target === null || $this->user()->can('view', $target);
    }

    protected function _rules(): array
    {
        return [
            'id' => 'required|int',
        ];
    }
}
