<?php

namespace Modules\JiraIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\JiraIntegration\Entities\Settings;

class CompanySettingsController extends Controller
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
            'get' => 'integration.jira-companysettings',
            'set' => 'integration.jira-companysettings',
        ];
    }

    public function get(): array
    {
        return [
            'enabled' => $this->settings->getEnabled(),
            'host' => $this->settings->getHost(),
        ];
    }

    public function set(Request $request): JsonResponse
    {
        $this->settings->setEnabled($request->post('enabled'));
        $this->settings->setHost($request->post('host'));

        return new JsonResponse(['success' => 'true', 'message' => 'Settings saved successfully']);
    }
}
