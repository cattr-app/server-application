<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use App\Models\Property;
use Filter;
use Illuminate\Http\Request;
use Validator;

class RedmineSettingsController extends AbstractRedmineController
{
    function getValidationRules()
    {
        return [
            'redmine_url' => 'required',
            'redmine_key' => 'required',
        ];
    }

    public function updateSettings(Request $request)
    {
        $request = Filter::process('request.redmine.settings.update', $request);
        $user = auth()->user();

        $validator = Validator::make(
            $request->all(),
            Filter::process('validation.redmine.settings.update', $this->getValidationRules())
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.redmine.settings.update'), [
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

        return response()->json(
            Filter::process('answer.success.redmine.settings.change', 'Updated!'),
            200
        );
    }

    public function getSettings(Request $request)
    {
        $user = auth()->user();

        $settingsArray = [
            'redmine_url'     => $this->getUserRedmineUrl($user->id),
            'redmine_api_key' => $this->getUserRedmineApiKey($user->id)
        ];

        return response()->json(
            $settingsArray,
            200
        );
    }
}