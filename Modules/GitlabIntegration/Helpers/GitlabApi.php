<?php

namespace Modules\GitlabIntegration\Helpers;

use App\Models\Property;
use App\Models\User;
use Gitlab\Client;
use Gitlab\ResultPager;
use Illuminate\Support\Facades\Log;

class GitlabApi
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var UserProperties
     */
    protected $userProperties;

    /**
     * @var string
     */
    protected $apiUrl;
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ResultPager
     */
    protected $pager;

    public function __construct(UserProperties $userProperties)
    {
        $this->userProperties = $userProperties;
    }

    protected function init(User $user): ?GitlabApi
    {
        $this->user = $user;

        $this->apiUrl = Property::where(['entity_type' => 'company', 'name' => 'gitlab_url'])->first();
        $this->apiUrl = $this->apiUrl ? $this->apiUrl->value : null;
        $this->apiKey = $this->userProperties->getApiKey($user->id);

        if (empty($this->apiUrl) || empty($this->apiKey)) {
            return null;
        }

        try {
            $this->client = Client::create($this->apiUrl)->authenticate($this->apiKey);
        } catch (\Throwable $throwable) {
            Log::error($throwable);
            return null;
        }
        $this->pager = new ResultPager($this->client);

        return $this;
    }

    public function getUserProjects()
    {
        return $this->pager->fetchAll($this->client->api('projects'), 'all');
    }

    public function getUserTasks()
    {
        return $this->pager->fetchAll($this->client->api('issues'), 'all', [null, [
            'scope' => 'assigned-to-me',
            'state' => 'opened',
        ]]);
    }

    public static function buildFromUser(User $user): ?GitlabApi
    {
        return app()->make(self::class)->init($user);
    }

    public function sendUserTime($projectId, $issue_iid, $duration)
    {
        return $this->client->issues->addSpentTime($projectId, $issue_iid, $duration);
    }
}
