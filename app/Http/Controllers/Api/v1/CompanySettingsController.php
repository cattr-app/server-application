<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\CompanySettings\StoreCompanySettings;
use App\Models\Priority;
use App\Services\CoreSettingsService;
use Illuminate\Http\JsonResponse;

class CompanySettingsController extends Controller
{
    /**
     * @var CoreSettingsService
     */
    protected CoreSettingsService $settings;

    /**
     * @var Priority
     */
    protected Priority $priorities;

    /**
     * CompanySettingsController constructor.
     * @param CoreSettingsService $settings
     * @param Priority $priorities
     */
    public function __construct(CoreSettingsService $settings, Priority $priorities)
    {
        parent::__construct();

        $this->settings = $settings;
        $this->priorities = $priorities;
    }

    /**
     * Returns the controller rules.
     *
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'index' => 'company-settings.index',
            'update' => 'company-settings.update',
        ];
    }

    /**
     * TODO: apidoc
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $settings = $this->settings->all();
        $priorities = $this->priorities->all();

        $data = $settings;
        $data['internal_priorities'] = $priorities;

        return new JsonResponse([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * TODO: apidoc
     *
     * @param StoreCompanySettings $request
     * @return JsonResponse
     */
    public function update(StoreCompanySettings $request): JsonResponse
    {
        $settings = $this->settings->set($request->validated());

        return new JsonResponse([
            'success' => true,
            'data' => $settings,
        ]);
    }
}
