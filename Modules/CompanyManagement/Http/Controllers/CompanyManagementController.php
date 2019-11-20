<?php

namespace Modules\CompanyManagement\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;

class CompanyManagementController extends Controller
{

    /**
     * @return mixed
     */
    public function getData()
    {
        $data = Property::where(['entity_type' => 'company'])->get();
        $toReturn = [];
        foreach ($data as $item) {
            $toReturn[$item->name] = $item->value;
        }

        return response()->json($toReturn);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function save()
    {
        $data = request()->except('token');
        foreach ($data as $k => $v) {
            Property::updateOrCreate([
                'entity_type' => 'company',
                'name' => $k,
            ], [
                'value' => $v,
                'entity_id' => 0
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => __('Company settings saved successfully')
        ], 200);
    }
}
