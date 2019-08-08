<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use App\Models\Priority;
use App\Models\Property;
use App\User;
use Filter;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Throwable;

/**
 * Class RedmineSettingsController
 *
 * @package Modules\RedmineIntegration\Http\Controllers
 */
class RedmineSettingsController extends AbstractRedmineController
{

    use ValidatesRequests;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var User|Authenticatable
     */
    protected $user;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->user = auth()->user();
    }

    /**
     * Update user's redmine settings
     *
     * @param  Request         $request
     * @param  UserRepository  $userRepository
     *
     * @return JsonResponse
     */
    public function updateSettings(Request $request, UserRepository $userRepository)
    {
        $request = Filter::process('request.redmine.settings.update', $request);

        try {
            $this->validate(
                $request,
                Filter::process('validation.redmine.settings.update', $this->getValidationRules())
            );
        } catch (Throwable $e) {
            return response()->json(
                Filter::process('answer.error.redmine.settings.update', [
                    'error' => 'Validation failed',
                ]),
                400
            );
        }

        $this->saveProperties();

        // Hell starts here
        $userRepository->setUserSendTime($this->user->id, $request->redmine_sync);
        $userRepository->setActiveStatusId($this->user->id, $request->redmine_on_activate_statuses['value']);
        $userRepository->setInactiveStatusId($this->user->id, $request->redmine_on_deactivate_statuses['value']);
        /** @noinspection PhpParamsInspection */
        $userRepository->setActivateStatuses(
            $this->user->id, $request->redmine_on_activate_statuses['reference']
        );
        /** @noinspection PhpParamsInspection */
        $userRepository->setDeactivateStatuses(
            $this->user->id, $request->redmine_on_deactivate_statuses['reference']
        );
        $userRepository->setOnlineTimeout($this->user->id, $request->redmine_online_timeout);
        // Hell ends here

        // If user doesn't have a redmine id, we'll mark it as new
        if (!$userRepository->getUserRedmineId($this->user->id)) {
            $userRepository->markAsNew($this->user->id);
        }

        return response()->json(Filter::process('answer.success.redmine.settings.change', 'Updated!'));
    }

    protected function saveProperties()
    {
        $this->processFilter('redmine.settings.url.change', 'REDMINE_URL', $this->request->redmine_url)
            ->processFilter('redmine.settings.url.change', 'REDMINE_KEY', $this->request->redmine_api_key)
            ->saveProperty('REDMINE_STATUSES', serialize($this->request->redmine_statuses))
            ->saveProperty('REDMINE_PRIORITIES', serialize($this->request->redmine_priorities));
    }

    /**
     * @param  string  $evt
     * @param  string  $name
     * @param  mixed   $value
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
     * Returns validation rules for 'updateSettings' request
     *
     * @return array
     */
    public function getValidationRules()
    {
        return [
            'redmine_url' => 'required',
            'redmine_api_key' => 'required',
            //'redmine_statuses' => 'required',
        ];
    }

    /**
     * Returns user's redmine settings
     *
     * @param  Request         $request
     * @param  UserRepository  $userRepository
     *
     * @return JsonResponse
     */
    public function getSettings(Request $request, UserRepository $userRepository)
    {
        $userId = auth()->user()->id;

        // This is something beyond my understanding of the real world
        $settingsArray = [
            'redmine_url' => $userRepository->getUserRedmineUrl($userId),
            'redmine_api_key' => $userRepository->getUserRedmineApiKey($userId),
            'redmine_statuses' => $userRepository->getUserRedmineStatuses($userId),
            'redmine_priorities' => $userRepository->getUserRedminePriorities($userId),
            'internal_priorities' => Priority::all(),

            'redmine_sync' => $userRepository->isUserSendTime($userId),
            'redmine_active_status' => $userRepository->getActiveStatusId($userId),
            'redmine_inactive_status' => $userRepository->getInactiveStatusId($userId),
            'redmine_on_activate_statuses' => [
                'value' => $userRepository->getActiveStatusId($userId),
                'reference' => $userRepository->getActivateStatuses($userId),
            ],
            'redmine_on_deactivate_statuses' => [
                'value' => $userRepository->getInactiveStatusId($userId),
                'reference' => $userRepository->getDeactivateStatuses($userId),
            ],
            'redmine_online_timeout' => $userRepository->getOnlineTimeout($userId),
        ];

        return response()->json($settingsArray);
    }

    /**
     * @return JsonResponse
     */
    public function getInternalPriorities(): JsonResponse
    {
        return response()->json(Priority::all());
    }
}
