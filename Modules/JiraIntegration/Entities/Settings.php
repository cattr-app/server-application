<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\Property;

class Settings
{
    public const API_HOST = 'JIRA_API_HOST';
    public const API_TOKEN = 'JIRA_API_TOKEN';

    public function get(string $entityType, int $entityId, string $propertyName, $default = '')
    {
        $params = [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'name' => $propertyName,
        ];

        $property = Property::where($params)->first(['value']);

        return $property ? $property->value : $default;
    }

    protected function set(string $entityType, int $entityId, string $propertyName, $value = ''): Property
    {
        $params = [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'name' => $propertyName,
        ];

        return Property::updateOrCreate($params, ['value' => $value]);
    }

    public function getHost(): string
    {
        return static::get(Property::COMPANY_CODE, 0, static::API_HOST, '');
    }

    public function setHost(string $key): Property
    {
        return static::set(Property::COMPANY_CODE, 0, static::API_HOST, $key);
    }

    public function getUserApiToken(int $userId): string
    {
        return static::get(Property::USER_CODE, $userId, static::API_TOKEN, '');
    }

    public function setUserApiToken(int $userId, string $key): Property
    {
        return static::set(Property::USER_CODE, $userId, static::API_TOKEN, $key);
    }
}
