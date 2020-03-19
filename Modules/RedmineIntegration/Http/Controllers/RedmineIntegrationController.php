<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Modules\RedmineIntegration\Models\ClientFactory;

/**
 * Class RedmineIntegrationController
 */
class RedmineIntegrationController extends AbstractRedmineController
{
    protected UserRepository $userRepo;
    protected ClientFactory $clientFactory;

    /**
     * Create a new instance.
     */
    public function __construct(UserRepository $userRepo, ClientFactory $clientFactory)
    {
        $this->userRepo = $userRepo;
        $this->clientFactory = $clientFactory;
    }
}
