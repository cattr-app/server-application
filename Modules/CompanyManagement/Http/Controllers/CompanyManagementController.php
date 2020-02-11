<?php

namespace Modules\CompanyManagement\Http\Controllers;

use App\Models\Priority;
use App\Models\Property;
use Illuminate\Routing\Controller;

class CompanyManagementController extends Controller
{
    protected static $casts = [
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

    protected function encodeField(string $name, $value)
    {
        if (!isset(static::$casts[$name])) {
            return $value;
        }

        switch (static::$casts[$name]) {
            case 'json':
                return json_encode($value);

            default:
                return $value;
        }
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        $data = Property::where(['entity_type' => Property::COMPANY_CODE])->get();
        $toReturn = [];
        foreach ($data as $item) {
            $name = $item->name;
            $toReturn[$name] = $this->decodeField($name, $item->value);
        }

        $toReturn['internal_priorities'] = Priority::all();

        return response()->json($toReturn);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function save()
    {
        $data = request()->except('token', 'internal_priorities');
        foreach ($data as $name => $value) {
            Property::updateOrCreate([
                'entity_type' => Property::COMPANY_CODE,
                'entity_id' => 0,
                'name' => $name,
            ], ['value' => $this->encodeField($name, $value)]);
        }

        return response()->json([
            'success' => true,
            'message' => __('Company settings saved successfully')
        ], 200);
    }
}
