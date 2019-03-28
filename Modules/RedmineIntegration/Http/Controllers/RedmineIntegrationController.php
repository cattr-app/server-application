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


    /**
     * get Redmine available task statuses
     *
     * @return JsonResponse
     */
    public function getStatuses(): JsonResponse
    {
        $userId = Auth::user()->id;
        $statuses = $this->userRepo->getUserRedmineStatuses($userId);

        return response()->json(
            $statuses,
            200,
        );
    }


    public function initRedmineClient(int $userId): Redmine\Client
    {
        $client = new RedmineClient($userId);

        return $client;
    }

}
