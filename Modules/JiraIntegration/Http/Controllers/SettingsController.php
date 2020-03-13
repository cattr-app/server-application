<?php

namespace Modules\JiraIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Validator;
use Modules\JiraIntegration\Entities\Settings;

class SettingsController extends Controller
{
    /**
     * @var Settings
     */
    private $settings;

    /**
     * SettingsController constructor.
     *
     * @param  Settings  $settings
     */
    public function __construct(Settings $settings)
    {
        parent::__construct();

        $this->settings = $settings;
    }

    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'get' => 'integration.jira',
            'set' => 'integration.jira',
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
        $apiToken = $this->settings->getUserApiToken($userId);

        return [
            'enabled'   => $this->settings->getEnabled(),
            'host'      => $this->settings->getHost(),
            'api_token' => preg_replace('/^(.{4}).*(.{4})$/i', '$1 ********* $2', $apiToken),
        ];
    }

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function set(Request $request)
    {
        $validator = Validator::make($request->all(), ['api_token' => 'string|required']);
        if ($validator->fails()) {
            return response()->json(['error' => 'Validation fail'], 400);
        }

        if (strpos($request->post('api_token'), '*') !== false) {
            return response()->json(['success' => 'true', 'message' => 'Nothing to update!']);
        }

        $userId = $request->user()->id;
        $this->settings->setUserApiToken($userId, $request->post('api_token'));

        return response()->json(['success' => 'true', 'message' => 'Settings saved successfully']);
    }
}
