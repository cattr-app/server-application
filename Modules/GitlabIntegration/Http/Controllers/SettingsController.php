<?php

namespace Modules\GitlabIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\GitlabIntegration\Helpers\UserProperties;

class SettingsController extends Controller
{
    /**
     * @var UserProperties
     */
    private $userProperties;

    /**
     * SettingsController constructor.
     *
     * @param  UserProperties  $userProperties
     */
    public function __construct(UserProperties $userProperties)
    {
        $this->userProperties = $userProperties;

        parent::__construct();
    }

    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'get' => 'integration.gitlab',
            'set' => 'integration.gitlab',
        ];
    }

    /**
     * @param  Request  $request
     *
     * @return array
     */
    public function get(Request $request)
    {
        $userId = $request->user()->id;

        return [
            'enabled' => Property::where(['entity_type' => 'company', 'name' => 'gitlab_enabled'])->first()
                ->value ?? false,
            'apikey' => $this->userProperties->getApiKey($userId)
        ];
    }

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function set(Request $request)
    {
        $userId = $request->user()->id;

        $validator = Validator::make(
            $request->all(), [
                'apikey' => 'string|required'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation fail',
            ], 400);
        }

        $this->userProperties->setApiKey($userId, $request->post('apikey'));

        return response()->json(['success' => 'true', 'message' => 'Settings saved successfully']);
    }
}
