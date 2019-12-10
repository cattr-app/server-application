<?php

namespace Modules\CompanyManagement\Http\Controllers;

use App\Models\Property;
use Illuminate\Routing\Controller;

class CompanyManagementController extends Controller
{
    protected static $json = ['redmine_statuses'];

    /**
     * @return mixed
     */
    public function getData()
    {
        $data = Property::where(['entity_type' => Property::COMPANY_CODE])->get();
        $toReturn = [];
        foreach ($data as $item) {
            $name = $item->name;
            $value = $item->value;
            if (in_array($name, static::$json)) {
                $value = json_decode($value, true);
            }

            $toReturn[$name] = $value;
        }

        return response()->json($toReturn);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function save()
    {
        $data = request()->except('token');
        foreach ($data as $name => $value) {
            if (in_array($name, static::$json)) {
                $value = json_encode($value);
            }

            Property::updateOrCreate([
                'entity_type' => Property::COMPANY_CODE,
                'entity_id' => 0,
                'name' => $name,
            ], ['value' => $value]);
        }

        return response()->json([
            'success' => true,
            'message' => __('Company settings saved successfully')
        ], 200);
    }
}
