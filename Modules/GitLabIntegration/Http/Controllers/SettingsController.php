<?php

namespace Modules\GitLabIntegration\Http\Controllers;

use Filter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\GitLabIntegration\Helpers\UserProperties;

class SettingsController extends Controller
{
    /**
     * @var UserProperties
     */
    private $userProperties;

    public function __construct(UserProperties $userProperties)
    {
        $this->userProperties = $userProperties;
    }

    public function get(Request $request)
    {
        $userId = $request->user()->id;

        return [
            'url' => $this->userProperties->getUrl($userId),
            'apikey' => $this->userProperties->getApiKey($userId)
        ];
    }

    public function set(Request $request)
    {
        $request = Filter::process('request.gitlab.settings.set', $request);
        $userId = $request->user()->id;

        $validator = Validator::make(
            $request->all(),
            Filter::process('validation.gitlab.settings.set', [
                'url' => 'string|required',
                'apikey' => 'string|required'
            ])
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process('answer.error.gitlab.settings.set', [
                    'error' => 'Validation fail',
                ]),
                400
            );
        }

        Filter::process(
            'gitlab.settings.url.change',
            $this->userProperties->setUrl($userId, $request->post('url'))
        );

        Filter::process(
            'gitlab.settings.apikey.change',
            $this->userProperties->setApiKey($userId, $request->post('apikey'))
        );

        return response()->json(
            Filter::process('answer.success.gitlab.settings.set', 'Setted!'),
            200
        );
    }
}
