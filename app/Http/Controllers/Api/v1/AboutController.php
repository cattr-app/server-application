<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Property;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AboutController extends Controller
{
    /**
     * Root URL of the statistics server.
     */
    private const STATS_ROOT_URL = 'https://stats.cattr.app';

    /**
     * Returns information about this instance.
     *
     * @param Client $client
     * @return JsonResponse
     */
    public function __invoke(Client $client): JsonResponse
    {
        $appVersion = config('app.version');
        $instanceId = Property::getProperty(Property::APP_CODE, 'INSTANCE_ID')->first();

        $headers = [];

        if ($instanceId) {
            $instanceId = $instanceId->getAttribute('value');

            $headers[] = [
                'x-cattr-instance' => $instanceId
            ];
        }

        try {
            $resultMsg = null;
            $knownVulnerable = null;
            $updateVersion = null;

            $response = $client->get("v1/version-check/{$appVersion}", [
                'base_uri' => self::STATS_ROOT_URL,
                'headers' => $headers
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);

            $knownVulnerable = $responseBody['knownVulnerable'];
            $updateVersion = $responseBody['updateVersion'];
        } catch (\Exception $e) {
            $resultMsg = 'Failed to get information from the server';
        }

        $result = [
            'success' => true,
            'message' => $resultMsg,
            'info' => [
                'app_version' => $appVersion,
                'instance_id' => $instanceId,
                'known_vulnerable' => $knownVulnerable,
                'update_version' => $updateVersion,
            ]
        ];

        return response()->json($result);
    }
}
