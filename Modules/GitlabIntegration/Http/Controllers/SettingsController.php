<?php

namespace Modules\GitlabIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Gitlab\Client;
use Gitlab\ResultPager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\GitlabIntegration\Helpers\UserProperties;
use Throwable;

class SettingsController extends Controller
{

    protected Client $client;
    protected $apiUrl;
    private UserProperties $userProperties;

    public function __construct(UserProperties $userProperties, Client $client)
    {
        $this->client = $client;
        $this->apiUrl = Property::where(['entity_type' => 'company', 'name' => 'gitlab_url'])->first();
        $this->apiUrl = $this->apiUrl ? $this->apiUrl->value : null;
        $this->userProperties = $userProperties;

        parent::__construct();
    }

    public static function getControllerRules(): array
    {
        return [
            'get' => 'integration.gitlab',
            'set' => 'integration.gitlab',
        ];
    }

    public function get(Request $request): array
    {
        $userId = $request->user()->id;
        $apiKey = $this->userProperties->getApiKey($userId);
        $hiddenKey = (bool)$apiKey
            ? preg_replace('/^(.{4}).*(.{4})$/i', '$1 ********* $2', $apiKey)
            : $apiKey;

        return [
            'enabled' => (bool)Property::where(['entity_type' => Property::COMPANY_CODE, 'name' => 'gitlab_enabled'])
                    ->first()
                    ->value ?? false,
            'apikey' => $hiddenKey
        ];
    }

    public function set(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        try {
            Validator::make(
                $request->all(),
                [
                    'apikey' => 'string|required'
                ]
            );

            $client = Client::create($this->apiUrl)
                ->authenticate($request->input('apikey'), Client::AUTH_URL_TOKEN);

            $fetcher = new ResultPager($client);
            $fetcher->fetch($client->api('users'), 'me');
        } catch (Throwable $throwable) {
            return new JsonResponse([
                'error' => 'Validation fail',
                'message' => 'Invalid API Key'
            ], 400);
        }

        if (strpos($request->post('apikey'), '*') !== false) {
            return new JsonResponse(['success' => 'true', 'message' => 'Nothing to update!']);
        }

        $this->userProperties->setApiKey($userId, $request->post('apikey'));

        return new JsonResponse(['success' => 'true', 'message' => 'Settings saved successfully']);
    }
}
