<?php

namespace Modules\JiraIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\{JsonResponse, Request};
use Modules\JiraIntegration\Entities\Settings;

class CompanySettingsController extends Controller
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
     * @return array
     */
    public function get()
    {
        return [
            'enabled' => $this->settings->getEnabled(),
            'host'    => $this->settings->getHost(),
        ];
    }

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function set(Request $request)
    {
        $this->settings->setEnabled($request->post('enabled'));
        $this->settings->setHost($request->post('host'));

        return response()->json(['success' => 'true', 'message' => 'Settings saved successfully']);
    }
}
