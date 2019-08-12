<?php


namespace Modules\Invoices\Entities\Repositories;

use App\Models\Project;
use App\Models\Property;
use Auth;
use DB;
use Exception;

/**
 * Class InvoicesRepository
 * @package Modules\Invoices\Entities\Repositories
 */
class InvoicesRepository
{
    const INVOICES_RATE = 'INVOICES_RATE';
    const SEPARATOR = '_';
    const DEFAULT_RATE = 'DEFAULT_RATE';

    /**
     * Get user rae for project
     * @param $projectId
     * @param $userId
     * @return string|null
     */
    public function getUserRateForProject($projectId, $userId): ?string
    {
        $userRateForProject = Property::where([
            ['entity_id', '=', $userId],
            ['entity_type', '=', Property::USER_CODE],
            ['name', '=', self::INVOICES_RATE . self::SEPARATOR . $projectId]
        ])->first();

        return $userRateForProject ? $userRateForProject->value : null;
    }

    /**
     * @param $userId
     * @param $projectId
     * @param $rate
     * @return mixed
     * @throws Exception
     */
    public function updateOrCreateUserRate($userId, $projectId, $rate)
    {
        $isExists = Property::where([
                ['entity_id', '=', $userId],
                ['entity_type', '=', Property::USER_CODE],
                ['name', '=', self::INVOICES_RATE . self::SEPARATOR . $projectId]
            ])->exists();

        if ($isExists) {
            $isSaved = Property::where([
                        ['entity_id', '=', $userId],
                        ['entity_type', '=', Property::USER_CODE],
                        ['name', '=', self::INVOICES_RATE . self::SEPARATOR . $projectId]
                    ])->update(['value' => $rate]);
        } else {
            $isSaved = Property::create([
                        'entity_id' => $userId,
                        'entity_type' => Property::USER_CODE,
                        'name' => self::INVOICES_RATE . self::SEPARATOR . $projectId,
                        'value' => $rate
                    ]);
        }

        if (!$isSaved) {
            throw new Exception("Cannot update or save user rates.", 400);
        }


        return [
            "userId" => $userId,
            "projectId" => $projectId,
            "rate" => $rate
        ];
    }

    public function getProjectsByUsers(array $userIds, array $projectIds)
    {
        $projectReports = DB::table('project_report')
            ->select('user_id', 'user_name', 'task_id', 'project_id', 'task_name', 'project_name', DB::raw('SUM(duration) as duration'))
            ->whereIn('user_id', $userIds)
            ->whereIn('project_id', $projectIds)
            ->whereIn('project_id', Project::getUserRelatedProjectIds(Auth::user()))
            ->groupBy('user_id', 'user_name', 'task_id', 'project_id', 'task_name', 'project_name')
            ->get();

        $users = [];


        foreach ($projectReports as $projectReport) {
            $project_id = $projectReport->project_id;
            $user_id = $projectReport->user_id;

            if (!isset($users[$user_id])) {
                $users[$user_id] = [
                    'id' => $user_id,
                    'full_name' => $projectReport->user_name,
                    'projects' => [],
                ];
            }

            if (!isset($users[$user_id]['projects'][$project_id])) {
                $users[$user_id]['projects'][$project_id] = [
                    'id' => $project_id,
                    'name' => $projectReport->project_name,
                ];
            }
        }


        foreach ($users as $user_id => $user) {
            $users[$user_id]['projects'] =  array_values($user['projects']);
        }

        $users = array_values($users);

        return $users;
    }

    public function getDefaultUsersRate(array $userIds)
    {
        $defaultValue = [];

        foreach ($userIds as $userId) {
            $rate = Property::where([
                ['entity_id', '=', $userId],
                ['entity_type', '=', Property::USER_CODE],
                ['name', '=', self::INVOICES_RATE . self::SEPARATOR . self::DEFAULT_RATE]
            ])->first();

            $defaultValue[] = [
                'userId' => $userId,
                'defaultRate' => $rate ? $rate->value : null
            ];
        }

        return $defaultValue;
    }

    public function setDefaultRateForUser(int $userId, string $defaultRate)
    {
        $isExists = Property::where([
            ['entity_id', '=', $userId],
            ['entity_type', '=', Property::USER_CODE],
            ['name', '=', self::INVOICES_RATE . self::SEPARATOR . self::DEFAULT_RATE]
        ])->exists();

        if ($isExists) {
            $isSaved = Property::where([
                ['entity_id', '=', $userId],
                ['entity_type', '=', Property::USER_CODE],
                ['name', '=', self::INVOICES_RATE . self::SEPARATOR . self::DEFAULT_RATE]
            ])->update(['value' => $defaultRate]);
        } else {
            $isSaved = Property::create([
                'entity_id' => $userId,
                'entity_type' => Property::USER_CODE,
                'name' => self::INVOICES_RATE . self::SEPARATOR . self::DEFAULT_RATE,
                'value' => $defaultRate
            ]);
        }

        if (!$isSaved) {
            throw new Exception("Cannot update or save user rates.", 400);
        }


        return [
            "userId" => $userId,
            "defaultRate" => $defaultRate
        ];
    }
}
