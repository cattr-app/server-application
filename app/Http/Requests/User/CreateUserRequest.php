<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Presenters\User\OrdinaryUserPresenter;
use App\Http\Requests\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CreateUserRequest extends FormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @param Request $request
     * @param OrdinaryUserPresenter $user
     * @return bool
     * @throws ValidationException
     */
    public function authorize(Request $request, OrdinaryUserPresenter $user): bool
    {
        if (auth()->check() && auth()->user()->hasRole('admin')) {
            return true;
        }

        if (!auth()->user()->can('update', User::find(request('id')))) {
            return false;
        }

        $fillableFields = $user->getFillable();
        $requestFields = array_keys($request->except('id'));

        $fieldsDiff = array_diff($requestFields, $fillableFields);

        if (count($fieldsDiff) > 0) {
            $errorMessages = [];

            foreach ($fieldsDiff as $fieldKey) {
                $errorMessages[$fieldKey] = __('You don\'t have permission to edit this field');
            }

            throw ValidationException::withMessages($errorMessages);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'full_name' => 'required|string',
            'email' => 'required|email',
            'user_language' => 'required',
            'password' => 'sometimes|required|min:6',
            'important' => 'bool',
            'active' => 'required|bool',
            'screenshots_active' => 'required|bool',
            'manual_time' => 'sometimes|required|bool',
            'screenshots_interval' => 'required|int|min:1|max:15',
            'computer_time_popup' => 'required|int|min:1',
            'timezone' => 'required|string',
            'role_id' => 'required|int|exists:role,id',
            'type' => 'required|string',
            'web_and_app_monitoring' => 'sometimes|required|bool',
        ];
    }
}
