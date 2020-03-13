<?php

namespace Modules\TrelloIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\TrelloIntegration\Entities\Settings;

/**
 * Class SettingsController
 * @package Modules\TrelloIntegration\Http\Controllers
 */
class SettingsController extends Controller
{
    /**
     * @var Settings
     */
    private Settings $settings;

    /**
     * SettingsController constructor.
     * @param Settings $settings
     */
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

    public function get(Request $request)
    {
        $userId = $request->user()->id;
        $apiToken = $this->settings->getUserApiKey($userId);

        return [
            // Hide the API key part
            'api_key' => preg_replace('/^(.{4}).*(.{4})$/i', '$1 ********* $2', $apiToken),
        ];
    }

    public function set(Request $request)
    {
        // Validate the API token
        $validator = Validator::make($request->all(), ['api_key' => 'string|required']);
        if ($validator->fails()) {
            return response()->json(['error' => 'Validation fail'], 400);
        }

        // Skip the token update, if it contains *
        if (strpos($request->post('api_key'), '*') !== false) {
            return response()->json(['success' => 'true', 'message' => 'Nothing to update!']);
        }

        $userId = $request->user()->id;
        $this->settings->setUserApiKey($userId, $request->post('api_key'));

        return response()->json(['success' => 'true', 'message' => 'Settings saved successfully']);
    }
}
