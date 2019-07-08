<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use Exception;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Modules\RedmineIntegration\Models\RedmineClient;
use Redmine\Client;

/**
 * Class RedmineIntegrationController
 *
 * @package Modules\RedmineIntegration\Http\Controllers
 */
class RedmineIntegrationController extends AbstractRedmineController
{

    /**
     * @var UserRepository
     */
    protected $userRepo;

    /**
     * Create a new instance.
     *
     * @param  UserRepository  $userRepo
     */
    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }


    /**
     * @param  int  $userId
     *
     * @return Client
     * @throws Exception
     */
    public function initRedmineClient(int $userId): Client
    {
        $client = new RedmineClient($userId);

        return $client;
    }

}
