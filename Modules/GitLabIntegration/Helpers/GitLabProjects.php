<?php

namespace Modules\GitLabIntegration\Helpers;

use App\User;
use Gitlab\{Client, ResultPager};
use Modules\GitLabIntegration\Models\GitLabProject;

class GitLabProjects
{
    /**
     * @var UserProperties
     */
    protected $properties;

    /**
     * @param UserProperties $properties
     */
    public function __construct(UserProperties $properties) {
        $this->properties = $properties;
    }

    /**
     * @param int $userId
     *
     * @return GitLabProject[]
     */
    public function syncUserProjects(int $userId): array
    {
        $url = $this->properties->getUrl($userId, '');
        $key = $this->properties->getApiKey($userId, '');
        if (empty($url) || empty($key)) {
            return [];
        }

        $client = Client::create($url)->authenticate($key, Client::AUTH_URL_TOKEN);
        $pager = new ResultPager($client);
        $data = $pager->fetchAll($client->api('projects'), 'all', [['per_page' => 100]]);

        $projects = [];
        foreach ($data as $project) {
            $id = (int)$project['id'];
            $name = $project['name'];

            /** @var GitLabProject $project */
            $project = GitLabProject::updateOrCreate([
                'gitlab_url'        => $url,
                'gitlab_project_id' => $id,
            ], [
                'name' => $name,
            ]);

            $project->users()->syncWithoutDetaching([$userId]);

            $projects[] = $project;
        }

        return $projects;
    }

    /**
     * @return GitLabProject[]
     */
    public function syncAllProjects(): array
    {
        $projects = [];
        $users = User::all();
        foreach ($users as $user) {
            $userProjects = $this->syncUserProjects($user->id);
            $projects = array_merge($projects, $userProjects);
        }

        return $projects;
    }

    /**
     * @param int $userId
     *
     * @return GitLabProject[]
     */
    public function getUserProjects(int $userId): array
    {
        $url = $this->properties->getUrl($userId, '');
        $key = $this->properties->getApiKey($userId, '');
        if (empty($url) || empty($key)) {
            return [];
        }

        return GitLabProject::with(['users' => function ($query) use ($userId) {
            $query->where('user_id', $userId);
        }]);
    }
}
