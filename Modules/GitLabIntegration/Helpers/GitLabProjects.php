<?php

namespace Modules\GitLabIntegration\Helpers;

use Gitlab\{Client, ResultPager};

class GitLabProjects
{
    /**
     * @var UserProperties
     */
    protected $properties;

    /**
     * @param UserProperties $properties
     */
    public function __construct(
        UserProperties $properties
    ) {
        $this->properties = $properties;
    }

    /**
     * @param int $userI
     *
     * @return array
     */
    public function getAll(int $userId): array
    {
        $url = $this->properties->getUrl($userId, '');
        $key = $this->properties->getApiKey($userId, '');
        if (empty($url) || empty($key)) {
            return [];
        }

        $client = Client::create($url)->authenticate($key, Client::AUTH_URL_TOKEN);
        $pager = new ResultPager($client);
        $projects = $pager->fetchAll($client->api('projects'), 'all', [['per_page' => 100]]);

        return array_map(function (array $project) {
            return [
                'id'   => $project['id'],
                'name' => $project['name'],
            ];
        }, $projects);
    }
}
