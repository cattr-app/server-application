<?php

namespace App\Http\Requests\CompanySettings;

use App\Enums\Role;
use App\Http\Requests\CattrFormRequest;
use Filter;

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
            'language' => 'sometimes|string',
            'default_priority_id' => 'sometimes|int',
        ];
    }
}
