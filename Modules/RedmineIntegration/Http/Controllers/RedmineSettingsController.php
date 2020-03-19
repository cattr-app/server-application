<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use App\EventFilter\Facades\Filter;
use App\Models\Priority;
use App\Models\Property;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Modules\RedmineIntegration\Models\ClientFactory;
use Modules\RedmineIntegration\Models\Settings;
use Throwable;

/**
 * Class RedmineSettingsController
 */
class RedmineSettingsController extends AbstractRedmineController
{

    use ValidatesRequests;

    protected Request $request;
    protected Settings $settings;

    /**
     * @var User|Authenticatable
     */
    protected $user;

    protected ClientFactory $clientFactory;


    public function __construct(Request $request, Settings $settings, ClientFactory $clientFactory)
    {
        $this->request = $request;
        $this->settings = $settings;
        $this->user = auth()->user();
        $this->clientFactory = $clientFactory;

        parent::__construct();
    }

    public static function getControllerRules(): array
    {
        return [
            'updateSettings' => 'integration.redmine',
            'getSettings' => 'integration.redmine',
            'getInternalPriorities' => 'integration.redmine',
        ];
    }

    /**
     * Update user's redmine settings
     */
    public function updateSettings(Request $request, UserRepository $userRepository): JsonResponse
    {
        $request = Filter::process('request.redmine.settings.update', $request);
        try {
            $this->validate(
                $request,
                Filter::process('validation.redmine.settings.update', $this->getValidationRules())
            );

            $client = $this->clientFactory->createUserClient(auth()->user()->id, $request->input('redmine_api_key'));
            $currentRedmineUser = $client->user->getCurrentUser() ?: [];

            if (!($currentRedmineUser['user'] ?? false)) {
                throw new Exception(
                    'Invalid API Key or Redmine is not available at the moment.'
                );
            }

        } catch (Throwable $e) {
            return new JsonResponse(
                Filter::process('answer.error.redmine.settings.update', [
                    'error' => 'Validation failed',
                    'message' => $e->getMessage()
                ]),
                400
            );
        }

        $this->saveProperties();

        // If user doesn't have a redmine id, we'll mark it as new
        if (!$userRepository->getUserRedmineId($this->user->id)) {
            $userRepository->markAsNew($this->user->id);
        }

        return new JsonResponse(Filter::process('answer.success.redmine.settings.change', 'Updated!'));
    }

    /**
     * Returns validation rules for 'updateSettings' request
     */
    public function getValidationRules(): array
    {
        return [
            'redmine_api_key' => 'required',
        ];
    }

    protected function saveProperties(): void
    {
        if (strpos($this->request->redmine_api_key, '*') !== false) {
            return;
        }
        $this->processFilter('redmine.settings.url.change', 'REDMINE_KEY', $this->request->redmine_api_key);
    }

    /**
     * @param string $evt
     * @param string $name
     * @param mixed $value
     *
     * @return RedmineSettingsController
     */
    protected function processFilter(string $evt, string $name, $value): self
    {
        Filter::process(
            $evt,
            $this->saveProperty($name, $value)
        );
        return $this;
    }

    /**
     * @param $name
     * @param $value
     *
     * @return RedmineSettingsController
     */
    protected function saveProperty($name, $value): self
    {
        Property::updateOrCreate(
            [
                'entity_id' => $this->user->id,
                'entity_type' => Property::USER_CODE,
                'name' => $name,
            ],
            [
                'value' => $value
            ]
        );

        return $this;
    }

    /**
     * Returns user's redmine settings
     */
    public function getSettings(Request $request, UserRepository $userRepository): JsonResponse
    {
        $userId = auth()->user()->id;
        $apiKey = $userRepository->getUserRedmineApiKey($userId);
        $hiddenKey = (bool)$apiKey ? preg_replace('/^(.{4}).*(.{4})$/i', '$1 ********* $2', $apiKey) : $apiKey;

        $settingsArray = [
            'enabled' => (bool)$this->settings->getEnabled(),
            'redmine_api_key' => $hiddenKey
        ];
        return new JsonResponse($settingsArray);
    }

    public function getInternalPriorities(): JsonResponse
    {
        return new JsonResponse(Priority::all());
    }
}
