<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseWebserviceController;

/**
 * Class WebserviceController
 *
 * @package App\Http\Controllers\Api\v1
 */
class WebserviceController extends BaseWebserviceController
{
    public function create()
    {
        return response()->json([
            'some' => 'data',
        ]);
    }

    public function show()
    {
        return response()->json([
            'some' => 'data',
        ]);
    }
}
