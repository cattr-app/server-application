<?php

namespace Modules\TrelloIntegration\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Modules\TrelloIntegration\Entities\ProjectRelation;
use Modules\TrelloIntegration\Entities\Settings;
use Modules\TrelloIntegration\Entities\TaskRelation;
use Modules\TrelloIntegration\Entities\UserRelation;
use Trello\Client;

class SyncTasks
{
    protected Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function synchronizeAll(): void
    {
        // If the company integration isn't set, do the return
        if (!$this->settings->getEnabled()) {
            return;
        }

        // Sync the tasks for all the users
        $users = User::all();
        foreach ($users as $user) {
            try {
                $this->synchronizeAssignedIssues($user);
            } catch (Exception $e) {
                echo "Something went wrong while Trello Tasks Synchronization\n";
                echo "Skip user {$user->full_name} while sync \n";
                echo "Error trace {$e->getTraceAsString()} \n\n";
                Log::error('Something went wrong while Trello Tasks Synchronization');
                Log::error("Skip user {$user->full_name} while sync");
                Log::error("Error trace {$e->getTraceAsString()}");
            }
        }
    }

    /**
     * @throws Exception
     */
    public function synchronizeAssignedIssues(User $user): void
    {
        // If the company integration for user's integration isn't set, do the return
        $apiKey = $this->settings->getUserApiKey($user->id);
        $appToken = $this->settings->getAuthToken();
        $organizationName = $this->settings->getOrganizationName();

        if (empty($apiKey) || empty($appToken) || empty($organizationName)) {
            return;
        }

        $client = new Client();
        $client->authenticate($appToken, $apiKey, Client::AUTH_URL_TOKEN);

        $userRelation = UserRelation::where('user_id', '=', $user->id)->first();

        if (!$userRelation) {
            $trelloUser = $client->members()->show('me', ['token' => $appToken]);

            if (!isset($trelloUser['id'])) {
                echo "Trello user not found \n";
                echo "Skip user {$user->full_name} while sync \n";
                Log::error("Skip user {$user->full_name} while sync");
                return;
            }
            $userRelation = UserRelation::create([
               'id'      => $trelloUser['id'],
               'user_id' => $user->id
            ]);
        }

        $userTrelloId = $userRelation->id;
        $organization = $client->organization()->show($organizationName, ['fields' => 'all']);

        if (!isset($organization['idBoards'])) {
            echo "Trello Boards were not found \n";
            Log::error("Trello Boards were not found. Organization {$organizationName}");
            return;
        }

        // Board === Project
        $boardIds = $organization['idBoards'];

        foreach ($boardIds as $boardId) {
            $board = $client->board()->show($boardId, ['boards' => 'open']);
            $projectMapping = [
                'company_id'  => 0,
                'name'        => $board['name'] ?? 'Trello Board without Name',
                'description' => $board['desc'] ?? '',
                'important'   => false,
            ];
            $relation = ProjectRelation::whereId($boardId)->first();

            if (!$relation) {
                $project = Project::create($projectMapping);

                ProjectRelation::create([
                    'id' => $boardId,
                    'project_id' => $project->id,
                ]);
            } else {
                $project = Project::find($relation->project_id);

                // If there no project - it was deleted from our app
                if (!$project) {
                    $relation->delete();
                    continue;
                }

                $project->name = $projectMapping['name'];
                $project->description = $projectMapping['description'];
                $project->save();
            }


            $this->syncTasksByBoardId($client, $boardId, $user->id, $userTrelloId, $project->id);
        }
    }

    /**
     * @throws Exception
     */
    private function syncTasksByBoardId(Client $client, string $boardId, int $userId, string $userTrelloId, int $projectId): void
    {
        $trelloTasks = $client->board()->cards()->all($boardId, [
            'closed'  => false,
        ]);

        if (!is_array($trelloTasks)) {
            echo "Trello Cards were not found for Board ID {$boardId} \n";
            Log::error("Trello Cards were not found for Board ID {$boardId}");
            return;
        }

        // Get only those tasks where user is a participant
        // Can`t rewrite as arrow function because of "use" outside scope variable
        $trelloTasks = array_filter($trelloTasks, static function ($task) use ($userTrelloId) {
            return in_array($userTrelloId, $task['idMembers']);
        });

        $trelloTaskIds = array_map(fn ($task): string => $task['id'], $trelloTasks);

        foreach ($trelloTasks as $trelloTask) {
            $taskMapping = [
                'task_name'   => $trelloTask['name'] ?? 'Trello Card without Name',
                'description' => $trelloTask['desc'] ?? '',
                'project_id'  => $projectId,
                'active'      => true,
                'assigned_by' => 0,
                'url'         => $trelloTask['url'] ?? '',
                'priority_id' => 2,
                'important'   => false,
                'user_id'     => $userId,
            ];

            $relation = TaskRelation::find($trelloTask['id']);

            if (!$relation) {
                $task = Task::create($taskMapping);

                TaskRelation::create([
                    'id' => $trelloTask['id'],
                    'task_id' => $task->id,
                ]);
            } else {
                $task = Task::find($relation->task_id);

                // If there no project - it was deleted from our app
                if (!$task) {
                    $relation->delete();
                    continue;
                }

                $task->task_name = $taskMapping['task_name'];
                $task->description = $taskMapping['description'];
                $task->user_id = $taskMapping['user_id'];
                $task->save();
            }
        }

        // If there are any relations which are assigned to another boardIds - that`s mean Boards were removed from Trello
        // And we need to delete it from our db as well
        $relationsToRemove = TaskRelation::whereNotIn('id', $trelloTaskIds)->get();

        /** @var $relationToRemove TaskRelation */
        foreach ($relationsToRemove as $relationToRemove) {
            $internalTask = Task::find($relationToRemove->task_id);
            if ($internalTask) {
                $internalTask->active = 0;
                $internalTask->save();
            }
        }
    }
}
