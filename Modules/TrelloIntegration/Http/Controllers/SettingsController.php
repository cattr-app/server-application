<?php

namespace Modules\TrelloIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\TrelloIntegration\Entities\Settings;

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
            'get' => 'integration.trello',
            'set' => 'integration.trello',
        ];
    }

    public function get(Request $request): array
    {
        $userId = $request->user()->id;
        $apiToken = $this->settings->getUserApiKey($userId);

        return [
            // Hide the API key part
            'api_key' => preg_replace('/^(.{4}).*(.{4})$/i', '$1 ********* $2', $apiToken),
        ];
    }

    public function set(Request $request): JsonResponse
    {
        // Validate the API token
        $validator = Validator::make($request->all(), ['api_key' => 'string|required']);
        if ($validator->fails()) {
            return new JsonResponse(['error' => 'Validation fail'], 400);
        }

        // Skip the token update, if it contains *
        if (strpos($request->post('api_key'), '*') !== false) {
            return new JsonResponse(['success' => 'true', 'message' => 'Nothing to update!']);
        }

        $userId = $request->user()->id;
        $this->settings->setUserApiKey($userId, $request->post('api_key'));

        return new JsonResponse(['success' => 'true', 'message' => 'Settings saved successfully']);
    }
}
