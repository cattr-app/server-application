<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Filter;
use Illuminate\Http\Request;

class RedmineSettingsController extends Controller
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
}
