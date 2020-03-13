<?php

namespace Modules\TrelloIntegration\Services;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Carbon;
use Modules\TrelloIntegration\Entities\Settings;
use Modules\TrelloIntegration\Entities\TaskRelation;
use Modules\TrelloIntegration\Entities\TimeRelation;
use Trello\Client;

/**
 * Class SyncTime
 * @package Modules\TrelloIntegration\Services
 */
class SyncTime
{
    /** @var Settings */
    protected Settings $settings;

    /**
     * SyncTime constructor.
     * @param Settings $settings
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function synchronizeAll()
    {
        // If the company integration isn't set, do the return
        if (!$this->settings->getEnabled()) {
            return;
        }

        // Sync the tasks for all the users
        $users = User::all();
        foreach ($users as $user) {
            $this->synchronizeUserTime($user);
        }
    }

    /**
     * @param User $user
     */
    public function synchronizeUserTime(User $user)
    {
        // If the company integration for user's integration isn't set, do the return
        $apiKey = $this->settings->getUserApiKey($user->id);;
        $appToken = $this->settings->getAuthToken();
        $organizationName = $this->settings->getOrganizationName();

        if (empty($apiKey) || empty($appToken) || empty($organizationName)) {
            return;
        }

        $client = new Client();
        $client->authenticate($appToken, $apiKey, Client::AUTH_URL_TOKEN);

        $timeRelations = TimeRelation::where('user_id', $user->id)->get();

        $result = [];
        foreach ($timeRelations as $timeRelation) {
            /** @var TimeRelation $timeRelation */
            /** @var TaskRelation $taskRelation */
            $taskRelation = $timeRelation->taskRelation;
            $cardID = $taskRelation->id;

            /** @var TimeInterval $timeInterval */
            $timeInterval = $timeRelation->timeInterval;
            $startAt = Carbon::parse($timeInterval->start_at);
            $endAt = Carbon::parse($timeInterval->end_at);
            $duration = $endAt->floatDiffInSeconds($startAt);

            $project = $timeInterval->task->project()->first();
            $projectName = $project->name;
            $formattedEndAt = $endAt->format('M d Y');

            if (!array_key_exists($projectName, $result)) {
                $result[$projectName][$cardID] = [
                    'card_id'  => $cardID,
                    'duration' => 0,
                    'start_at' => $startAt->format('M d Y'),
                    'end_at'   => $formattedEndAt,
                ];
            }

            $result[$projectName][$cardID]['duration'] += $duration;
            $result[$projectName][$cardID]['end_at'] = $result[$projectName][$cardID]['end_at'] === $formattedEndAt
                ? $result[$projectName][$cardID]['end_at']
                : $formattedEndAt;

            $timeRelation->delete();
        }

        foreach ($result as $boardName => $cards) {
            foreach ($cards as $id => $card) {
                $formattedDuration = Carbon::now()->subSeconds($card['duration'])->diffForHumans(null, true, true, 3);
                $message = "via Cattr \n";
                $message .= "User: {$user->full_name} \n\n";
                $message .= "Project: {$boardName} \n";
                $message .= "Date Range:  {$card['start_at']} - {$card['end_at']} \n\n";
                $message .= "Spent Time:  {$formattedDuration} \n";

                $client->card()->actions()->addComment($card['card_id'], $message);
            }
        }
    }
}
