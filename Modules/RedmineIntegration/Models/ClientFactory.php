<?php

namespace Modules\RedmineIntegration\Models;

use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Redmine\Client;

class ClientFactory
{
    /**
     * @var Settings
     */
    protected $settings;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    public function __construct(
        Settings $settings,
        UserRepository $userRepository
    ) {
        $this->settings = $settings;
        $this->userRepository = $userRepository;
    }

    public function createCompanyClient() : Client
    {
        $url = $this->settings->getURL();
        if (empty($url)) {
            throw new \Exception('Empty URL', 404);
        }

        $key = $this->settings->getAPIKey();
        if (empty($key)) {
            throw new \Exception('Empty API key', 404);
        }

        return new Client($url, $key);
    }

    public function createUserClient(int $userID, string $key = null) : Client
    {
        $url = $this->settings->getURL();
        if (empty($url)) {
            throw new \Exception('Empty URL', 404);
        }

        $key = $key ?? $this->userRepository->getUserRedmineApiKey($userID);
        if (empty($key)) {
            throw new \Exception('Empty API key', 404);
        }

        return new Client($url, $key);
    }
}
