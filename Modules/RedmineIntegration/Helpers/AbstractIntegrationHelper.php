<?php

namespace Modules\RedmineIntegration\Helpers;

use Redmine;
use Modules\RedmineIntegration\Models\RedmineClient;

/**
 * Class AbstractIntegrationHelper
 *
 * Abstract integration helper class
 *
 * @package Modules\RedmineIntegration\Entities
 */
abstract class AbstractIntegrationHelper
{
    /**
     * Init Redmine client object
     *
     * @param $userId User's id in our system
     * @return Redmine\Client
     */
    public function initRedmineClient(int $userId): Redmine\Client
    {
        $client = new RedmineClient($userId);

        return $client;
    }
}
