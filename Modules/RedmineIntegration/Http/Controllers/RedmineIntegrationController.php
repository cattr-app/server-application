<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\RedmineIntegration\Helpers\ProjectIntegrationHelper;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;

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
     * @param UserRepository $userRepo
     */
    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }


    public function initRedmineClient(int $userId): Redmine\Client
    {
        $client = new RedmineClient($userId);

        return $client;
    }

}
