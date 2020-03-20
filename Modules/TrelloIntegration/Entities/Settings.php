<?php

namespace Modules\TrelloIntegration\Entities;

use App\Models\Property;

class Settings
{
    // Property names
    public const ENABLED = 'trello_enabled';
    public const API_KEY = 'trello_api_key';
    public const AUTH_TOKEN = 'trello_auth_token';
    public const ORGANIZATION_NAME = 'trello_organization_name';
    public const SYNC_TIME_PERIOD = 'trello_sync_time_period';

    // Properties receiving

    public function getEnabled(): bool
    {
        return (bool)$this->get(Property::COMPANY_CODE, 0, static::ENABLED, 0);
    }

    // Property install

    public function get(string $entityType, int $entityId, string $propertyName, $default = '')
    {
        $params = [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'name' => $propertyName,
        ];

        $property = Property::where($params)->first(['value']);

        return isset($property) ? $property->value : $default;
    }

    // Activity integration's getter and setter

    public function setEnabled(bool $enabled): Property
    {
        return $this->set(Property::COMPANY_CODE, 0, static::ENABLED, $enabled);
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

    public function getTimeSyncPeriod(): bool
    {
        return (bool)$this->get(Property::COMPANY_CODE, 0, static::SYNC_TIME_PERIOD, 'daily');
    }

    public function setTimeSyncPeriod(string $period): Property
    {
        return $this->set(Property::COMPANY_CODE, 0, static::SYNC_TIME_PERIOD, $period);
    }

    // Trello Auth Token getter and setter
    public function setAuthToken(string $authToken): string
    {
        return $this->set(Property::COMPANY_CODE, 0, static::AUTH_TOKEN, $authToken);
    }

    public function getAuthToken(): string
    {
        return $this->get(Property::COMPANY_CODE, 0, static::AUTH_TOKEN, '');
    }

    public function setOrganizationName($organizationName): string
    {
        return $this->set(Property::COMPANY_CODE, 0, static::ORGANIZATION_NAME, $organizationName);
    }

    public function getOrganizationName(): string
    {
        return $this->get(Property::COMPANY_CODE, 0, static::ORGANIZATION_NAME, '');
    }

    // User API key's getter and setter
    public function getUserApiKey(int $userId): string
    {
        return $this->get(Property::USER_CODE, $userId, static::API_KEY, '');
    }

    public function setUserApiKey(int $userId, string $token): Property
    {
        return $this->set(Property::USER_CODE, $userId, static::API_KEY, $token);
    }
}
