<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\About\ManageStorageRequest;
use App\Helpers\ModuleHelper;
use App\Helpers\ReportHelper;
use App\Helpers\StorageCleaner;
use App\Helpers\StorageCleanupStatus;
use App\Jobs\RotateScreenshots;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Settings;
use Throwable;

class AboutController extends Controller
{
    private function statsClient(): PendingRequest
    {
        return Http::baseUrl(config('app.stats_collector_url') . '/v2')
            ->withHeaders(['x-cattr-instance' => Settings::scope('core')->get('instance')])
            ->acceptJson();
    }

    /**
     * Get Available Report Formats
     *
     * Retrieves the list of available report formats and their corresponding MIME types.
     */

    public function reports(): JsonResponse
    {
        return responder()->success([
            'types' => ReportHelper::getAvailableReportFormats(),
        ])->respond();
    }

    /**
     * Get Application and Module Information
     *
     * Retrieves information about the application instance, modules, and image version details.
     */

    public function __invoke(ManageStorageRequest $request): JsonResponse
    {
        $imageVersion = getenv('IMAGE_VERSION', true) ?: null;

        $releaseInfo = $this->requestReleaseInfo();
        $modulesInfo = $this->requestModulesInfo();
        $imageInfo = ($imageVersion) ? $this->requestImageInfo($imageVersion) : false;

        return responder()->success([
            'app' => [
                'version' => config('app.version'),
                'instance_id' => Settings::scope('core')->get('instance'),
                'vulnerable' => optional($releaseInfo)->vulnerable,
                'last_version' => optional($releaseInfo)->lastVersion,
                'message' => optional($releaseInfo)->flashMessage,
            ],
            'modules' => $modulesInfo,
            'image' => [
                'version' => $imageVersion,
                'vulnerable' => optional($imageInfo)->vulnerable,
                'last_version' => optional($imageInfo)->lastVersion,
                'message' => optional($imageInfo)->flashMessage,
            ]
        ])->respond();
    }

    private function requestReleaseInfo(): ?object
    {
        try {
            return $this->statsClient()->get('release/' . config('app.version'))->throw()->object();
        } catch (Exception) {
            return null;
        }
    }

    /**
     * The collector receives every installed module, the response lists only the enabled ones.
     */
    private function requestModulesInfo(): array
    {
        $modules = $this->enrichModulesInfo(ModuleHelper::getModulesInfo());

        return array_values(array_filter($modules, static fn (array $module) => $module['enabled']));
    }

    private function enrichModulesInfo(array $local): array
    {
        try {
            $remote = $this->statsClient()->post('modules', $local)->throw()->json('modules');
        } catch (Exception) {
            return $local;
        }

        if (!is_array($remote)) {
            return $local;
        }

        // The local registry is the source of truth for which modules are installed:
        // the collector only enriches the modules it knows about and must not hide the rest.
        $known = [];
        foreach ($remote as $module) {
            if (is_array($module) && isset($module['name']) && is_string($module['name'])) {
                $known[mb_strtolower($module['name'])] = $module;
            }
        }

        return array_map(
            static fn (array $module) => array_merge($known[mb_strtolower($module['name'])] ?? [], $module),
            $local
        );
    }

    private function requestImageInfo(string $imageVersion): ?object
    {
        try {
            return $this->statsClient()->get("image/$imageVersion")->throw()->object();
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Get Storage Information
     *
     * Retrieves information about the storage space, thinning status, and available screenshots.
     */

    public function storage(ManageStorageRequest $request): JsonResponse
    {
        $status = StorageCleanupStatus::get();

        return responder()->success([
            'space' => [
                'left' => StorageCleaner::getFreeSpace(),
                'used' => StorageCleaner::getUsedSpace(),
                'total' => config('cleaner.total_space'),
            ],
            'threshold' => config('cleaner.threshold'),
            'need_thinning' => StorageCleaner::needThinning(),
            // false on object storage (S3): no free space to measure, rotation is a no-op
            'rotation_supported' => StorageCleaner::isSupported(),
            // Scans every eligible interval and checks its file on each request, also while the About page polls
            // a running cleanup (every 10 s). A lightweight status endpoint for that polling, or a count cached
            // between cleanups, may be needed on large local disks; measure first under which conditions
            // (number of intervals, disk, poll rate next to a running cleanup) and cover the choice with tests.
            'screenshots_available' => StorageCleaner::countAvailableScreenshots(),
            'thinning' => [
                'now' => $status['status'] === StorageCleanupStatus::RUNNING,
                'last' => StorageCleanupStatus::last(),
                'status' => $status['status'],
                'requested_at' => $status['requested_at'],
                'finished_at' => $status['finished_at'],
            ]
        ])->respond();
    }
    /**
     * Request Storage Cleanup
     *
     * Queues the screenshot thinning. The request is accepted, not started: follow thinning.status
     * of GET /storage. Nothing is queued while a thinning is already queued or running.
     */
    public function startStorageClean(ManageStorageRequest $request): JsonResponse
    {
        $runId = StorageCleanupStatus::queue();

        if ($runId !== null) {
            try {
                RotateScreenshots::dispatch($runId);
            } catch (Throwable $e) {
                StorageCleanupStatus::failRun($runId);

                throw $e;
            }
        }

        return responder()->success()->respond(204);
    }
}
