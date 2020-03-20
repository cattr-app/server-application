<?php

namespace Modules\JiraIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\JiraIntegration\Entities\Settings;

class SettingsController extends Controller
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        parent::__construct();

        $this->settings = $settings;
    }

    public static function getControllerRules(): array
    {
        return [
            'get' => 'integration.jira',
            'set' => 'integration.jira',
        ];
    }

    public function get(Request $request): array
    {
        $userId = $request->user()->id;
        $apiToken = $this->settings->getUserApiToken($userId);

        return [
            'enabled' => $this->settings->getEnabled(),
            'host' => $this->settings->getHost(),
            'api_token' => preg_replace('/^(.{4}).*(.{4})$/i', '$1 ********* $2', $apiToken),
        ];
    }

    public function set(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), ['api_token' => 'string|required']);
        if ($validator->fails()) {
            return new JsonResponse(['error' => 'Validation fail'], 400);
        }

        if (strpos($request->post('api_token'), '*') !== false) {
            return new JsonResponse(['success' => 'true', 'message' => 'Nothing to update!']);
        }

        $userId = $request->user()->id;
        $this->settings->setUserApiToken($userId, $request->post('api_token'));

        return new JsonResponse(['success' => 'true', 'message' => 'Settings saved successfully']);
    }
}
