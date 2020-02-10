<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\Property;

class Settings
{
    public const ENABLED   = 'jira_enabled';
    public const API_HOST  = 'jira_api_host';
    public const API_TOKEN = 'jira_api_token';

    public function get(string $entityType, int $entityId, string $propertyName, $default = '')
    {
        $params = [
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'name'        => $propertyName,
        ];

        $property = Property::where($params)->first(['value']);

        return $property ? $property->value : $default;
    }

    protected function set(string $entityType, int $entityId, string $propertyName, $value = ''): Property
    {
        $params = [
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'name'        => $propertyName,
        ];

        return Property::updateOrCreate($params, ['value' => $value]);
    }

    public function getEnabled(): bool
    {
        return (bool)static::get(Property::COMPANY_CODE, 0, static::ENABLED, 0);
    }

    public function setEnabled(bool $enabled): Property
    {
        return static::set(Property::COMPANY_CODE, 0, static::ENABLED, $enabled);
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
