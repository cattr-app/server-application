<?php

namespace Modules\GitlabIntegration\Helpers;

use App\Models\Property;

class UserProperties
{
    public const URL = 'GITLAB_URL';

    public const API_KEY = 'GITLAB_APIKEY';

    public function getUrl(int $userId, string $default = ''): string
    {
        return $this->get($userId, static::URL, $default);
    }

    public function get(int $userId, string $propertyName, $default = '')
    {
        $property = Property::where('entity_id', '=', $userId)
            ->where('entity_type', '=', Property::USER_CODE)
            ->where('name', '=', $propertyName)->first(['value']);

        return $property ? $property->value : $default;
    }

    public function getApiKey(int $userId, string $default = ''): string
    {
        return $this->get($userId, static::API_KEY, '');
    }

    public function setUrl(int $userId, string $url): Property
    {
        return $this->set($userId, static::URL, $url);
    }

    protected function set($userId, string $propertyName, $value): Property
    {
        $params = [
            'entity_id' => $userId,
            'entity_type' => Property::USER_CODE,
            'name' => $propertyName,
        ];

        /** @var Property $property */
        $property = Property::query()->where($params)->first();

        if (!$value) {
            $value = '';
        }

        if (!$property) {
            $params['value'] = $value;
            $property = Property::query()->create($params);
        } else {
            $property->value = $value;
            $property->save();
        }
        return $property;
    }

    public function setApiKey(int $userId, string $apikey): Property
    {
        return $this->set($userId, static::API_KEY, $apikey);
    }
}
