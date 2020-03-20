<?php

namespace Modules\TrelloIntegration\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\TrelloIntegration\Entities\Settings;

class CompanySettingsController extends Controller
{
    private Settings $settings;

    // Get the Settings's instance via DI and save it to the $settings field
    public function __construct(Settings $settings)
    {
        parent::__construct();
        $this->settings = $settings;
    }

    // Endpoint controller's access rules
    public static function getControllerRules(): array
    {
        return [
            'get' => 'integration.trello-companysettings',
            'set' => 'integration.trello-companysettings',
        ];
    }

    // Get the integration's settings
    public function get(): array
    {
        return [
            'enabled' => $this->settings->getEnabled(),
            'token' => preg_replace('/^(.{4}).*(.{4})$/i', '$1 ********* $2', $this->settings->getAuthToken()),
            'organization_name' => $this->settings->getOrganizationName(),
            'period' => $this->settings->getTimeSyncPeriod(),
        ];
    }

    // Save the integration's settings
    public function set(Request $request): JsonResponse
    {
        $this->settings->setEnabled($request->post('enabled'));
        $this->settings->setAuthToken($request->post('token'));
        $this->settings->setOrganizationName($request->post('organization_name'));
        $this->settings->setTimeSyncPeriod($request->post('sync_time_period'));

        return new JsonResponse(['success' => 'true', 'message' => 'Settings saved successfully']);
    }
}
