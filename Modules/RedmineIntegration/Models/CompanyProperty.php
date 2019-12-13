<?php

namespace Modules\RedmineIntegration\Models;

use App\Models\Property;

abstract class CompanyProperty
{
    protected const ENTITY_TYPE = Property::COMPANY_CODE;
    protected const ENTITY_ID = 0;

    protected function get(string $name): ?Property
    {
        return Property::where([
            'entity_type' => static::ENTITY_TYPE,
            'entity_id' => static::ENTITY_ID,
            'name' => $name,
        ])->first();
    }

    protected function set(string $name, string $value): Property
    {
        return Property::updateOrCreate([
            'entity_type' => static::ENTITY_TYPE,
            'entity_id' => static::ENTITY_ID,
            'name' => $name,
        ], ['value' => $value]);
    }
}
