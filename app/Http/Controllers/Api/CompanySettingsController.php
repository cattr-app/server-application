<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanySettings\UpdateCompanySettingsRequest;
use App\Http\Transformers\CompanySettingsTransformer;
use App\Models\Priority;
use Illuminate\Http\JsonResponse;
use Settings;

class CompanySettingsController extends Controller
{
    /**
     * List
     *
     * Returns all company settings.
     *
     * Modules can add their own keys to the settings object.
     *
     * @response array{status: int, success: true, data: array{
     *     timezone: string|null,
     *     language: string|null,
     *     work_time: int,
     *     color: list<mixed>,
     *     internal_priorities: list<\App\Models\Priority>,
     *     heartbeat_period: int,
     *     auto_thinning: bool,
     *     screenshots_state: int,
     *     env_screenshots_state: int,
     *     default_priority_id: int,
     * }}
     */
    public function index(): JsonResponse
    {
        return responder()->success(
            array_merge(
                Settings::scope('core')->all(),
                ['internal_priorities' => Priority::all()]
            ),
            new CompanySettingsTransformer
        )->respond();
    }

    /**
     * Update
     *
     * Updates the specified company settings.
     */
    public function update(UpdateCompanySettingsRequest $request): JsonResponse
    {
        Settings::scope('core')->set($request->validated());

        return responder()->success()->respond(204);
    }
    /**
     * Get Offline Sync Public Key
     *
     * Retrieves the public key for offline synchronization.
     *
     * Any authenticated user may call it.
     *
     * `key` is `null` if no key is stored.
     *
     * @response array{status: int, success: true, data: array{key: string|null}}
     */
    public function getOfflineSyncPublicKey(): JsonResponse
    {
        return responder()->success(
            [ 'key' => Settings::scope('core.offline-sync')->get('public_key')],
        )->respond();
    }
}
