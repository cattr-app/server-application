<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Modules\RedmineIntegration\Models\ClientFactory;

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
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * Create a new instance.
     *
     * @param  UserRepository  $userRepo
     * @param  ClientFactory   $clientFactory
     */
    public function __construct(UserRepository $userRepo, ClientFactory $clientFactory)
    {
        $this->userRepo = $userRepo;
        $this->clientFactory = $clientFactory;
    }
}
