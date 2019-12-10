<?php

namespace Modules\CompanyManagement\Models;

use App\Models\Property;

class RedmineSettings
{
    protected const ENTITY_TYPE = Property::COMPANY_CODE;
    protected const ENTITY_ID = 0;
    protected const REDMINE_URL = 'redmine_url';
    protected const REDMINE_API_KEY = 'redmine_api_key';
    protected const REDMINE_STATUSES = 'redmine_statuses';

    protected function getProperty(string $name): ?Property
    {
        return Property::where([
            'entity_type' => static::ENTITY_TYPE,
            'entity_id' => static::ENTITY_ID,
            'name' => $name,
        ])->first();
    }

    protected function setProperty(string $name, string $value): Property
    {
        return Property::updateOrCreate([
            'entity_type' => static::ENTITY_TYPE,
            'entity_id' => static::ENTITY_ID,
            'name' => $name,
        ], ['value' => $value]);
    }

    public function getURL(): string
    {
        $property = $this->getProperty(static::REDMINE_URL);

        return isset($property) ? $property->value : '';
    }

    public function setURL(string $value)
    {
        $this->setProperty(static::REDMINE_URL, $value);
    }

    public function getAPIKey(): string
    {
        $property = $this->getProperty(static::REDMINE_API_KEY);

        return isset($property) ? $property->value : '';
    }

    public function setAPIKey(string $value)
    {
        $this->setProperty(static::REDMINE_API_KEY, $value);
    }

    public function getStatuses(): array
    {
        $property = $this->getProperty(static::REDMINE_STATUSES);

        return isset($property) ? (json_decode($property->value, true) ?: []) : [];
    }

    public function setStatuses(array $value)
    {
        $this->setProperty(static::REDMINE_STATUSES, json_encode($value));
    }

    public function statusExistsByID(int $id): bool
    {
        $statuses = $this->getStatuses();
        foreach ($statuses as $status) {
            if ($status['id'] == $id) {
                return true;
            }
        }

        return false;
    }

    public function addStatus(int $id, string $name, bool $isActive, bool $isClosed)
    {
        $statuses = $this->getStatuses();
        $statuses[] = [
            'id' => $id,
            'name' => $name,
            'is_active' => $isActive,
            'is_closed' => $isClosed,
        ];

        $this->setStatuses($statuses);
    }
}
