<?php

namespace Modules\CompanyManagement\Http\Controllers;

use App\Models\Priority;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class CompanyManagement extends Controller
{
    protected static $casts = [
        'color' => 'json',
        'gitlab_enabled' => 'int',
        'redmine_enabled' => 'int',
        'redmine_statuses' => 'json',
        'redmine_priorities' => 'json',
        'redmine_active_status' => 'int',
        'redmine_inactive_status' => 'int',
        'redmine_activate_on_statuses' => 'json',
        'redmine_deactivate_on_statuses' => 'json',
        'redmine_sync' => 'int',
        'redmine_online_timeout' => 'int',
    ];

    public function getData(): JsonResponse
    {
        $data = Property::where(['entity_type' => Property::COMPANY_CODE])->get();
        $toReturn = [];
        foreach ($data as $item) {
            $name = $item->name;
            $toReturn[$name] = $this->decodeField($name, $item->value);
        }

        $toReturn['internal_priorities'] = Priority::all();

        return new JsonResponse($toReturn);
    }

    protected function decodeField(string $name, $value)
    {
        if (!isset(static::$casts[$name])) {
            return $value;
        }

        switch (static::$casts[$name]) {
            case 'json':
                return json_decode($value, true);

            case 'int':
                return (int)$value;

            default:
                return $value;
        }
    }

    public function save(Request $request): JsonResponse
    {
        $data = $request->except('token', 'internal_priorities');
        foreach ($data as $name => $value) {
            $this->setCompanyData($name, $this->encodeField($name, $value));
        }

        return new JsonResponse([
            'success' => true,
            'message' => __('Company settings saved successfully')
        ]);
    }

    public function setCompanyData(string $setting, string $value): void
    {
        Property::updateOrCreate([
            'entity_type' => Property::COMPANY_CODE,
            'entity_id' => 0,
            'name' => $setting,
        ], ['value' => $value]);
    }

    protected function encodeField(string $name, $value)
    {
        if (!isset(static::$casts[$name])) {
            return $value;
        }

        $i = static::$casts[$name];
        if ($i === 'json') {
            // To avoid possible double encoding
            if (is_string($value)) {
                return $value;
            }

            return json_encode($value);
        }

        return $value;
    }

    public function editLanguage(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'language' => ['required', Rule::in(config('app.languages'))]
        ]);

        $this->setCompanyData('language', $validatedData['language']);

        return new JsonResponse([
            'success' => true,
            'message' => __('Language saved successfully')
        ]);
    }

    public function editTimeZone(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'timezone' => 'required|timezone'
        ]);

        $this->setCompanyData('timezone', $validatedData['timezone']);

        return new JsonResponse([
            'success' => true,
            'message' => __('Timezone saved successfully')
        ]);
    }
}
