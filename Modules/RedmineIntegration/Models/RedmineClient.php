<?php

namespace Modules\RedmineIntegration\Models;

use App\Models\Property;
use Exception;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Redmine\Client;

class RedmineClient extends Client
{
    /**
     * RedmineClient constructor.
     *
     * @param $userId
     *
     * @throws Exception
     */
    public function __construct($userId)
    {
        $userRepository = new UserRepository();
        $url = Property::where(['entity_type' => 'company', 'name' => 'redmine_url'])->first()->value;
        $apiKey = $userRepository->getUserRedmineApiKey($userId);
        $pass = null;

        if (empty($url)) {
            throw new Exception('Empty url', 404);
        }

        parent::__construct($url, $apiKey, $pass);
    }

}
