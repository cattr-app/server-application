<?php

namespace Modules\TrelloIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\TrelloIntegration\Entities\Settings;

/**
 * Class CompanySettingsController
 * @package Modules\TrelloIntegration\Http\Controllers
 */
class CompanySettingsController extends Controller
{
    /**
     * @var Settings
     */
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
    public function get()
    {
        return [
            'enabled' => $this->settings->getEnabled(),
            'token'   => preg_replace('/^(.{4}).*(.{4})$/i', '$1 ********* $2', $this->settings->getAuthToken()),
            'organization_name'   => $this->settings->getOrganizationName(),
            'period'   => $this->settings->getTimeSyncPeriod(),
        ];
    }

    // Save the integration's settings
    public function set(Request $request)
    {
        $this->settings->setEnabled($request->post('enabled'));
        $this->settings->setAuthToken($request->post('token'));
        $this->settings->setOrganizationName($request->post('organization_name'));
        $this->settings->setTimeSyncPeriod($request->post('sync_time_period'));

        return response()->json(['success' => 'true', 'message' => 'Settings saved successfully']);
    }
}
