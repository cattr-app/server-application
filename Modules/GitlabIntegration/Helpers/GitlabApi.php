<?php

namespace Modules\GitlabIntegration\Helpers;

use App\Models\Property;
use App\Models\User;
use Gitlab\Client;
use Gitlab\ResultPager;
use Illuminate\Support\Facades\Log;
use Throwable;

class GitlabApi
{
    protected User $user;
    protected UserProperties $userProperties;
    protected string $apiUrl;
    protected string $apiKey;
    protected Client $client;
    protected ResultPager $pager;

    public function __construct(UserProperties $userProperties)
    {
        $this->userProperties = $userProperties;
    }

    public static function buildFromUser(User $user): ?GitlabApi
    {
        return app()->make(self::class)->init($user);
    }

    protected function init(User $user): ?GitlabApi
    {
        $this->user = $user;
        $url = Property::where(['entity_type' => 'company', 'name' => 'gitlab_url'])->first();
        if (!$url) {
            return null;
        }
        $this->apiUrl = $url->value ?: null;
        $this->apiKey = $this->userProperties->getApiKey($user->id);
        if (empty($this->apiUrl) || empty($this->apiKey)) {
            return null;
        }

        try {
            $this->client = Client::create($this->apiUrl)->authenticate($this->apiKey, Client::AUTH_URL_TOKEN);
            $this->pager = new ResultPager($this->client);
            $this->pager->fetch($this->client->api('users'), 'me');
        } catch (Throwable $throwable) {
            Log::error($throwable);
            return null;
        }
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

    public function sendUserTime($projectId, $issue_iid, $duration)
    {
        return $this->client->issues->addSpentTime($projectId, $issue_iid, $duration);
    }
}
