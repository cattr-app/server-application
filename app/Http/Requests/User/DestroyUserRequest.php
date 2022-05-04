<?php

namespace App\Http\Requests\User;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\User;
use App\Presenters\User\OrdinaryUserPresenter;
use App\Http\Requests\CattrFormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DestroyUserRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorizeValidated(): bool
    {
        return $this->user()->can('destroy', User::find(request('id')));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function _rules(): array
    {
        return [
            'id' => 'required|int',
        ];
    }
}
