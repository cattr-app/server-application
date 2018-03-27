<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Redmine;
use App\Models\Property;

abstract class AbstractRedmineController extends Controller
{
    protected function initRedmineClient($userId)
    {
        $client = new Redmine\Client($this->getUserRedmineUrl($userId), $this->getUserRedmineApiKey($userId));

        return $client;
    }

    protected function getUserRedmineUrl($userId)
    {
        $redmineUrlProperty =  Property::where('entity_id', '=', $userId)
            ->where('entity_type', '=', Property::USER_CODE)
            ->where('name', '=', 'REDMINE_URL')->first();

        return $redmineUrlProperty ? $redmineUrlProperty->value : '';
    }

    protected function getUserRedmineApiKey($userId)
    {
        $redmineApiKeyProperty =  Property::where('entity_id', '=', $userId)
            ->where('entity_type', '=', Property::USER_CODE)
            ->where('name', '=', 'REDMINE_KEY')->first();

        return $redmineApiKeyProperty? $redmineApiKeyProperty->value : '';
    }


}
