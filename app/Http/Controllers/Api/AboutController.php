<?php

namespace App\Http\Controllers\Api;

use App\Console\Commands\RotateScreenshots;
use App\Helpers\ModuleHelper;
use App\Helpers\ReportHelper;
use App\Helpers\StorageCleaner;
use App\Helpers\Version;
use Artisan;
use Cache;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use JsonException;
use Settings;

class AboutController extends Controller
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('app.stats_collector_url') . '/v2/',
            'headers' => ['x-cattr-instance' => Settings::scope('core')->get('instance')],
        ]);
    }

    public function reports(): JsonResponse
    {
        return responder()->success([
            'types' => ReportHelper::getAvailableReportFormats(),
        ])->respond();
    }

    /**
     * Returns information about this instance.
     * @throws JsonException
     */
    public function __invoke(): JsonResponse
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
            return json_decode(
                $this->client->get('release/' . config('app.version'))->getBody()->getContents(),
                false,
                512,
                JSON_THROW_ON_ERROR | JSON_THROW_ON_ERROR
            );
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @throws JsonException
     */
    private function requestModulesInfo(): array
    {
        $options = [
            'json' => ModuleHelper::getModulesInfo(),
        ];

        try {
            return array_map(static function ($el) {
                $el['version'] = (string)(new Version($el['name']));

                return $el;
            }, json_decode(
                $this->client->post('modules', $options)->getBody()->getContents(),
                true,
                512,
                JSON_THROW_ON_ERROR | JSON_THROW_ON_ERROR
            )['modules']);
        } catch (Exception) {
            return ModuleHelper::getModulesInfo();
        }
    }

    private function requestImageInfo(string $imageVersion): ?object
    {
        try {
            return json_decode(
                $this->client->get("image/$imageVersion")->getBody()->getContents(),
                false,
                512,
                JSON_THROW_ON_ERROR | JSON_THROW_ON_ERROR
            );
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @throws BindingResolutionException
     */
    public function storage(): JsonResponse
    {
        return responder()->success([
            'space' => [
                'left' => StorageCleaner::getFreeSpace(),
                'used' => StorageCleaner::getUsedSpace(),
                'total' => config('cleaner.total_space'),
            ],
            'threshold' => config('cleaner.threshold'),
            'need_thinning' => StorageCleaner::needThinning(),
            'screenshots_available' => StorageCleaner::countAvailableScreenshots(),
            'thinning' => [
                'now' => Cache::store('octane')->get('thinning_now'),
                'last' => Cache::store('octane')->get('last_thin'),
            ]
        ])->respond();
    }

    public function startStorageClean(): JsonResponse
    {
        Artisan::queue(RotateScreenshots::class);

        return responder()->success()->respond(204);
    }
}
