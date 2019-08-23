<?php

namespace Modules\GitlabIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\GitlabIntegration\Helpers\UserProperties;

class SettingsController extends Controller
{
    /**
     * @var UserProperties
     */
    private $userProperties;

    public function __construct(UserProperties $userProperties)
    {
        $this->userProperties = $userProperties;
    }

    public function get(Request $request)
    {
        $userId = $request->user()->id;

        return [
            'url' => $this->userProperties->getUrl($userId),
            'apikey' => $this->userProperties->getApiKey($userId)
        ];
    }

    public function set(Request $request)
    {
        $userId = $request->user()->id;

        $validator = Validator::make(
            $request->all(), [
                'url' => 'string|required',
                'apikey' => 'string|required'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation fail',
            ], 400);
        }

        $this->userProperties->setUrl($userId, $request->post('url'));
        $this->userProperties->setApiKey($userId, $request->post('apikey'));

        return response()->json('Setted!', 200);
    }
}
