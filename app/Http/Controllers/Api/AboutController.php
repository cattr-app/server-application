<?php

namespace App\Http\Controllers\Api;

use App\Console\Commands\RotateScreenshots;
use App\Helpers\ModuleHelper;
use App\Helpers\StorageCleaner;
use App\Helpers\Version;
use Artisan;
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

    private ?string $statsRootUrl;
    private string $statsReleaseUrl;
    private string $statsImagesUrl;
    private string $statsModulesUrl;

    public function __construct(Client $client)
    {
        $this->statsRootUrl = config('app.stats_collector_url');
        $this->statsReleaseUrl = "$this->statsRootUrl/release/";
        $this->statsImagesUrl = "$this->statsRootUrl/image/";
        $this->statsModulesUrl = "$this->statsRootUrl/modules/";
        $this->client = $client;
    }

    /**
     * @param string|null $instanceId
     * @return array
     * @throws JsonException
     */
    private function requestReleaseInfo(?string $instanceId = null): array
    {
        $url = $this->statsReleaseUrl . config('app.version');
        $options = ['headers' => ($instanceId) ? ['x-cattr-instance' => $instanceId] : []];

        return json_decode(
            $this->client->get($url, $options)->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR | JSON_THROW_ON_ERROR
        );
    }

    /**
     * @param $instanceId
     * @return array
     * @throws JsonException
     */
    private function requestModulesInfo($instanceId): array
    {
        $options = [
            'json' => ModuleHelper::getModulesInfo(),
            'headers' => ($instanceId) ? ['x-cattr-instance' => $instanceId] : []
        ];

        return array_map(static function ($el) {
            $el['version'] = (string)(new Version($el['name']));

            return $el;
        }, json_decode(
            $this->client->post($this->statsModulesUrl, $options)->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR | JSON_THROW_ON_ERROR
        )['modules']);
    }

    /**
     * @param string $imageVersion
     * @return array
     * @throws JsonException
     */
    private function requestImageInfo(string $imageVersion): array
    {
        $url = $this->statsImagesUrl . $imageVersion;
        return json_decode(
            $this->client->get($url)->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR | JSON_THROW_ON_ERROR
        );
    }

    /**
     * Returns information about this instance.
     */
    public function __invoke(): JsonResponse
    {
        if (!$this->statsRootUrl) {
            return new JsonResponse([
                'message' => 'Stats collector URL is not set'
            ], 500);
        }

        $instanceId = Settings::scope('core')->get('instance');
        $imageVersion = getenv('IMAGE_VERSION');

        try {
            $releaseInfo = $this->requestReleaseInfo($instanceId);
            $modulesInfo = $this->requestModulesInfo($instanceId);
            $imageInfo = ($imageVersion) ? $this->requestImageInfo($imageVersion) : false;
        } catch (Exception $e) {
            return new JsonResponse([
                'message' => 'Failed to get information from the server'
            ]);
        }

        return new JsonResponse([
            'app' => [
                'version' => config('app.version'),
                'instance_id' => $instanceId,
                'vulnerable' => $releaseInfo['vulnerable'],
                'last_version' => $releaseInfo['lastVersion'],
                'message' => $releaseInfo['flashMessage'],
            ],
            'modules' => $modulesInfo,
            'image' => (!$imageInfo) ? false : [
                'version' => $imageVersion,
                'vulnerable' => $imageInfo['vulnerable'],
                'last_version' => $imageInfo['lastVersion'],
                'message' => $imageInfo['flashMessage'],
            ]
        ]);
    }

    /**
     * @throws BindingResolutionException
     */
    public function storage(): JsonResponse
    {
        return response()->json([
            'space' => [
                'left' => StorageCleaner::getFreeSpace(),
                'used' => StorageCleaner::getUsedSpace(),
                'total' => config('cleaner.total_space'),
            ],
            'threshold' => config('cleaner.threshold'),
            'need_thinning' => StorageCleaner::needThinning(),
            'screenshots_available' => StorageCleaner::countAvailableScreenshots(),
            'thinning' => [
                'now' => cache('thinning_now'),
                'last' => cache('last_thin'),
            ]
        ]);
    }

    public function startStorageClean(): JsonResponse
    {
        Artisan::queue(RotateScreenshots::class);

        return response()->json(['message' => 'Ok']);
    }
}
