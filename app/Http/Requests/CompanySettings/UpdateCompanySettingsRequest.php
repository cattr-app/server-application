<?php

namespace App\Http\Requests\CompanySettings;

use App\Enums\Role;
use App\Enums\ScreenshotEnabledOptions;
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
            'enable_screenshots' => 'sometimes|in:' . implode(',', array_map(fn($item) => $item->value, ScreenshotEnabledOptions::cases())),
            'language' => 'sometimes|string',
            'default_priority_id' => 'sometimes|int',
        ];
    }
}
