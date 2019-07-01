<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use App\Models\Priority;
use App\Models\Property;
use Filter;
use Illuminate\Http\Request;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Validator;

/**
 * Class RedmineSettingsController
 *
 * @package Modules\RedmineIntegration\Http\Controllers
 */
class RedmineSettingsController extends AbstractRedmineController
{
    /**
     * Returns validation rules for 'updateSettings' request
     *
     * @return array
     */
    public function getValidationRules()
    {
        return [
            'redmine_url' => 'required',
            'redmine_key' => 'required',
            //'redmine_statuses' => 'required',
        ];
    }

    /**
     * Update user's redmine settings
     *
     * @param  Request         $request
     * @param  UserRepository  $userRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSettings(Request $request, UserRepository $userRepository)
    {
        $request = Filter::process('request.redmine.settings.update', $request);
        $user = auth()->user();

        $validator = Validator::make(
            $request->all(),
            Filter::process('validation.redmine.settings.update', $this->getValidationRules())
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process('answer.error.redmine.settings.update', [
                    'error' => 'Validation failed',
                ]),
                400
            );
        }

        $propertyRedmineUrl = Filter::process(
            'redmine.settings.url.change',
            Property::updateOrCreate(
                [
                    'entity_id' => $user->id,
                    'entity_type' => Property::USER_CODE,
                    'name' => 'REDMINE_URL',

                ],
                [
                    'value' => $request->redmine_url
                ]
            )
        );

        $propertyRedmineUrl = Filter::process(
            'redmine.settings.url.change',
            Property::updateOrCreate(
                [
                    'entity_id' => $user->id,
                    'entity_type' => Property::USER_CODE,
                    'name' => 'REDMINE_KEY',
                ],
                [
                    'value' => $request->redmine_key
                ]
            )
        );

        Property::updateOrCreate(
            [
                'entity_id' => $user->id,
                'entity_type' => Property::USER_CODE,
                'name' => 'REDMINE_STATUSES',
            ],
            [
                'value' => serialize($request->redmine_statuses),
            ]
        );

        Property::updateOrCreate(
            [
                'entity_id' => $user->id,
                'entity_type' => Property::USER_CODE,
                'name' => 'REDMINE_PRIORITIES',
            ],
            [
                'value' => serialize($request->redmine_priorities),
            ]
        );

        $userRepository->setUserSendTime($user->id, $request->redmine_sync);
        $userRepository->setActiveStatusId($user->id, $request->redmine_active_status);
        $userRepository->setDeactiveStatusId($user->id, $request->redmine_deactive_status);
        $userRepository->setActivateStatuses($user->id, $request->redmine_activate_statuses);
        $userRepository->setDeactivateStatuses($user->id, $request->redmine_deactivate_statuses);
        $userRepository->setOnlineTimeout($user->id, $request->redmine_online_timeout);

        //If user hasn't a redmine id in our system => mark user as NEW
        $userRedmineId = $userRepository->getUserRedmineId($user->id);

        if (!$userRedmineId) {
            $userRepository->markAsNew($user->id);
        }

        return response()->json(Filter::process('answer.success.redmine.settings.change', 'Updated!'));
    }

    /**
     * Returns user's redmine settings
     *
     * @param  Request         $request
     * @param  UserRepository  $userRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings(Request $request, UserRepository $userRepository)
    {
        $userId = auth()->user()->id;

        $settingsArray = [
            'redmine_url' => $userRepository->getUserRedmineUrl($userId),
            'redmine_api_key' => $userRepository->getUserRedmineApiKey($userId),
            'redmine_statuses' => $userRepository->getUserRedmineStatuses($userId),
            'redmine_priorities' => $userRepository->getUserRedminePriorities($userId),
            'internal_priorities' => Priority::all(),

            'redmine_sync' => $userRepository->isUserSendTime($userId),
            'redmine_active_status' => $userRepository->getActiveStatusId($userId),
            'redmine_inactive_status' => $userRepository->getDeactiveStatusId($userId),
            'redmine_on_activate_statuses' => $userRepository->getActivateStatuses($userId),
            'redmine_on_deactivate_statuses' => $userRepository->getDeactivateStatuses($userId),
            'redmine_online_timeout' => $userRepository->getOnlineTimeout($userId),
        ];

        return response()->json($settingsArray);
    }
}
