<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use App\Models\Property;
use Filter;
use Illuminate\Http\Request;

class RedmineSettingsController extends AbstractRedmineController
{
    public function updateSettings(Request $request)
    {
        $request = Filter::process('request.redmine.settings.change', $request);
        $user = auth()->user();

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
