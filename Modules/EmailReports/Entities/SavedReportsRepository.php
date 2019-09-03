<?php

namespace Modules\EmailReports\Entities;


use App\Models\Project;
use App\Models\Property;
use App\User;
use DB;
use Modules\Invoices\Entities\Repositories\InvoicesRepository;

class SavedReportsRepository
{

    public function createReportFromDecodedData($fetchedReport, $userIds, $dates) : array
    {
        $projectIds = $fetchedReport->project_ids ? json_decode($fetchedReport->project_ids) : Project::all('id')->pluck('id');
        $projects = $this->getProjectReports($userIds, $projectIds, $dates);

        if (empty($projects)) {
            return [];
        }

        // Let`s set default/unique user rate for project
        foreach ($projects as $projectId => $data) {
            foreach ($data['users'] as $userId => $userData) {
                $defaultRate = $this->getUserDefaultRate($userId);
                $uniqueProjectRate = $this->getUserRateForProject($userId, $projectId);
                $projects[$projectId]['users'][$userId] += ['rate' => $uniqueProjectRate ?? $defaultRate];
            }
        }

        return $projects;
    }

    protected function getUserDefaultRate($userId)
    {
        $rate = Property::where([
            ['entity_id', '=', $userId],
            ['entity_type', '=', Property::USER_CODE],
            ['name', '=', InvoicesRepository::INVOICES_RATE . InvoicesRepository::SEPARATOR . InvoicesRepository::DEFAULT_RATE]
        ])->first();

        return $rate->value ?? null;
    }

    protected function getUserRateForProject(int $userId, int $projectId)
    {
        $userRateForProjects = Property::select('name', 'value')
            ->where([
                ['entity_id', '=', $userId],
                ['entity_type', '=', Property::USER_CODE],
                ['name', '=', InvoicesRepository::INVOICES_RATE . InvoicesRepository::SEPARATOR . $projectId]
            ])
            ->first();

        return $userRateForProjects->value ?? null;
    }

    protected function getProjectReports($uids, $pids, $dates)
    {
        $endAt = $dates['endAt'];
        $projectReports = DB::table('project_report')
            ->select('user_id', 'user_name', 'task_id', 'project_id', 'task_name', 'project_name', DB::raw('SUM(duration) as duration'))
            ->whereIn('user_id', $uids)
            ->whereIn('project_id', $pids)
            ->where('date', '>=', $dates['startAt'])
            ->when($endAt, function ($query) use ($endAt) {
                return $query->where('date', '<', $endAt);
            })
            ->groupBy('user_id', 'user_name', 'task_id', 'project_id', 'task_name', 'project_name')
            ->get();


        $projects = [];
        foreach ($projectReports as $projectReport) {
            $project_id = $projectReport->project_id;
            $user_id = $projectReport->user_id;

            if ($project_id === null) {
                continue;
            }

            if (!isset($projects[$project_id])) {
                $projects[$project_id] = [
                    'id' => $project_id,
                    'name' => $projectReport->project_name,
                    'users' => [],
                    'project_time' => 0,
                ];
            }

            if (!isset($projects[$project_id]['users'][$user_id])) {
                $projects[$project_id]['users'][$user_id] = [
                    'id' => $user_id,
                    'full_name' => $projectReport->user_name,
                    'tasks' => [],
                    'tasks_time' => 0,
                ];
            }


            $projects[$project_id]['users'][$user_id]['tasks'][] = [
                'id' => $projectReport->task_id,
                'project_id' => $projectReport->project_id,
                'user_id' => $projectReport->user_id,
                'task_name' => $projectReport->task_name,
                'duration' => (int)$projectReport->duration,
            ];

            $projects[$project_id]['users'][$user_id]['tasks_time'] += $projectReport->duration;
            $projects[$project_id]['project_time'] += $projectReport->duration;
        }
        return $projects;
    }
}
