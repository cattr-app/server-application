<?php

namespace App\Http\Requests\CompanySettings;

use App\Enums\Role;
use App\Enums\ScreenshotsState;
use App\Http\Requests\CattrFormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCompanySettingsRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->hasRole(Role::ADMIN);
    }

    public function _rules(): array
    {
        return [
            'timezone' => 'sometimes|required|timezone',
            'work_time' => 'sometimes|int',
            'auto_thinning' => 'sometimes|boolean',
            'screenshots_state' => ['sometimes', 'required', new Enum(ScreenshotsState::class)],
            'language' => 'sometimes|string',
            'default_priority_id' => 'sometimes|int',
        ];
    }
}
