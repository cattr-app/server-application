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

    /**
     * @api             {get} /about/reports Get Available Report Formats
     * @apiDescription  Retrieves the list of available report formats and their corresponding MIME types.
     *
     * @apiVersion      4.0.0
     * @apiName         GetReportFormats
     * @apiGroup        About
     *
     * @apiSuccess {Object}     types                        List of available report formats and their MIME types.
     * @apiSuccess {String}     types.csv                    MIME type for CSV format.
     * @apiSuccess {String}     types.xlsx                   MIME type for XLSX format.
     * @apiSuccess {String}     types.pdf                    MIME type for PDF format.
     * @apiSuccess {String}     types.xls                    MIME type for XLS format.
     * @apiSuccess {String}     types.ods                    MIME type for ODS format.
     * @apiSuccess {String}     types.html                   MIME type for HTML format.
     *
     * @apiSuccessExample {json} Success Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "types": {
     *        "csv": "text/csv",
     *        "xlsx": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
     *        "pdf": "application/pdf",
     *        "xls": "application/vnd.ms-excel",
     *        "ods": "application/vnd.oasis.opendocument.spreadsheet",
     *        "html": "text/html"
     *      }
     *  }
     *
      * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */

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
    /**
     * @api             {get} /about Get Application and Module Information
     * @apiDescription  Retrieves information about the application instance, modules, and image version details.
     *
     * @apiVersion      4.0.0
     * @apiName         GetAppInfo
     * @apiGroup        About
     *
     * @apiSuccess {Object}     app                          Application information.
     * @apiSuccess {String}     app.version                  The current version of the application.
     * @apiSuccess {String}     app.instance_id              The unique identifier of the application instance.
     * @apiSuccess {Boolean}    app.vulnerable               Indicates if the application is vulnerable.
     * @apiSuccess {String}     app.last_version             The latest version available for the application.
     * @apiSuccess {String}     app.message                  Any important message related to the application.
     *
     * @apiSuccess {Object[]}   modules                      List of modules integrated into the application.
     * @apiSuccess {String}     modules.name                 Name of the module.
     * @apiSuccess {String}     modules.version              Current version of the module.
     * @apiSuccess {Boolean}    modules.enabled              Indicates if the module is enabled.
     *
     * @apiSuccess {Object}     image                        Information about the image version.
     * @apiSuccess {String}     image.version                The version of the image (if available).
     * @apiSuccess {Boolean}    image.vulnerable             Indicates if the image is vulnerable.
     * @apiSuccess {String}     image.last_version           The latest available version for the image.
     * @apiSuccess {String}     image.message                Any important message related to the image.
     *
     * @apiSuccessExample {json} Success Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "app": {
     *        "version": "dev",
     *        "instance_id": null,
     *        "vulnerable": null,
     *        "last_version": null,
     *        "message": null
     *      },
     *      "modules": [
     *        {
     *          "name": "JiraIntegration",
     *          "version": "3.0.0",
     *          "enabled": false
     *        }
     *      ],
     *        "image": {
     *        "version": null,
     *        "vulnerable": null,
     *        "last_version": null,
     *        "message": null
     *     }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
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
    /**
     * @api             {get} /storage Get Storage Information
     * @apiDescription  Retrieves information about the storage space, thinning status, and available screenshots.
     *
     * @apiVersion      4.0.0
     * @apiName         GetStorageInfo
     * @apiGroup        Storage
     *
     * @apiSuccess {Object}     space                          Information about the storage space.
     * @apiSuccess {Number}     space.left                     The amount of free space left (in bytes).
     * @apiSuccess {Number}     space.used                     The amount of space currently used (in bytes).
     * @apiSuccess {Number}     space.total                    The total amount of storage space available (in bytes).
     *
     * @apiSuccess {Number}     threshold                      The storage usage threshold percentage before thinning is needed.
     * @apiSuccess {Boolean}    need_thinning                  Indicates if the storage requires thinning.
     * @apiSuccess {Number}     screenshots_available          The number of available screenshots in the storage.
     *
     * @apiSuccess {Object}     thinning                       Information about the thinning process.
     * @apiSuccess {Boolean}    thinning.now                   Indicates if the thinning process is currently ongoing.
     * @apiSuccess {String}     thinning.last                  Timestamp of the last thinning process.
     *
     * @apiSuccessExample {json} Success Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "space": {
     *        "left": 26579533824,
     *        "used": 178705711104,
     *        "total": 205285244928
     *      },
     *      "threshold": 75,
     *      "need_thinning": true,
     *      "screenshots_available": 0,
     *      "thinning": {
     *        "now": null,
     *        "last": null
     *      }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
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
   /**
    * @api             {get} /storage Get Storage Information
    * @apiDescription  Retrieves information about the storage space, thinning status, and available screenshots.
    *
    * @apiVersion      4.0.0
    * @apiName         GetStorageInfo
    * @apiGroup        Storage
    *
    * @apiSuccessExample {json} Response Example
    *  HTTP/1.1 204 No Content
    *  {
    *  }
    *
    * @apiUse          400Error
    * @apiUse          UnauthorizedError
    *
    */
    public function startStorageClean(): JsonResponse
    {
        Artisan::queue(RotateScreenshots::class);

        return responder()->success()->respond(204);
    }
}
