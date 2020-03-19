<?php

namespace Modules\RedmineIntegration\Models;

class Settings extends CompanyProperty
{
    protected const REDMINE_ENABLED = 'redmine_enabled';
    protected const REDMINE_URL = 'redmine_url';
    protected const REDMINE_API_KEY = 'redmine_api_key';
    protected const REDMINE_SYNC = 'redmine_sync';
    protected const REDMINE_ONLINE_TIMEOUT = 'redmine_online_timeout';

    public function getEnabled(): int
    {
        $property = $this->get(static::REDMINE_ENABLED);

        return isset($property) ? $property->value : 0;
    }

    public function setEnabled(int $value): void
    {
        $this->set(static::REDMINE_ENABLED, $value);
    }

    public function getURL(): string
    {
        $property = $this->get(static::REDMINE_URL);

        return isset($property) ? $property->value : '';
    }

    public function setURL(string $value): void
    {
        $this->set(static::REDMINE_URL, $value);
    }

    public function getAPIKey(): string
    {
        $property = $this->get(static::REDMINE_API_KEY);

        return isset($property) ? $property->value : '';
    }

    public function setAPIKey(string $value): void
    {
        $this->set(static::REDMINE_API_KEY, $value);
    }

    public function getSendTime(): int
    {
        $property = $this->get(static::REDMINE_SYNC);

        return isset($property) ? $property->value : 0;
    }

    public function setSendTime(int $value): void
    {
        $this->set(static::REDMINE_SYNC, $value);
    }

    public function getOnlineTimeout(): int
    {
        $property = $this->get(static::REDMINE_ONLINE_TIMEOUT);

        return isset($property) ? $property->value : 0;
    }

    public function setOnlineTimeout(int $value): void
    {
        $this->set(static::REDMINE_ONLINE_TIMEOUT, $value);
    }
}
