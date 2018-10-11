<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use App\Models\Priority;
use App\Models\Property;
use Filter;
use Illuminate\Http\Request;
use Validator;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;

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
    function getValidationRules()
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
     * @param Request $request
     * @param UserRepository $userRepository
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
                    'error' => 'Validation fail',
                ]),
                400
            );
        }

        $propertyRedmineUrl = Filter::process(
            'redmine.settings.url.change',
            Property::updateOrCreate(
                [
                    'entity_id'   => $user->id,
                    'entity_type' => Property::USER_CODE,
                    'name'        => 'REDMINE_URL',

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
                    'entity_id'   => $user->id,
                    'entity_type' => Property::USER_CODE,
                    'name'        => 'REDMINE_KEY',
                ],
                [
                    'value' => $request->redmine_key
                ]
            )
        );

        Property::updateOrCreate(
            [
                'entity_id'   => $user->id,
                'entity_type' => Property::USER_CODE,
                'name'        => 'REDMINE_STATUSES',
            ],
            [
                'value' => serialize($request->redmine_statuses),
            ]
        );

        Property::updateOrCreate(
            [
                'entity_id'   => $user->id,
                'entity_type' => Property::USER_CODE,
                'name'        => 'REDMINE_PRIORITIES',
            ],
            [
                'value' => serialize($request->redmine_priorities),
            ]
        );

        //If user hasn't a redmine id in our system => mark user as NEW
        $userRedmineId = $userRepository->getUserRedmineId($user->id);

        if (!$userRedmineId) {
            $userRepository->markAsNew($user->id);
        }

        return response()->json(
            Filter::process('answer.success.redmine.settings.change', 'Updated!'),
            200
        );
    }

    /**
     * Returns user's redmine settings
     *
     * @param Request $request
     * @param UserRepository $userRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings(Request $request, UserRepository $userRepository)
    {
        $userId = auth()->user()->id;

        $settingsArray = [
            'redmine_url'      => $userRepository->getUserRedmineUrl($userId),
            'redmine_api_key'  => $userRepository->getUserRedmineApiKey($userId),
            'redmine_statuses' => $userRepository->getUserRedmineStatuses($userId),
            'redmine_priorities' => $userRepository->getUserRedminePriorities($userId),
            'internal_priorities' => Priority::all(),
        ];

        return response()->json(
            $settingsArray,
            200
        );
    }
}
