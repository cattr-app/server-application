<?php


namespace Modules\Invoices\Entities\Repositories;

use App\Models\Property;
use App\User;
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
     * @param $projectIds
     * @param $userId
     * @return array|null
     */
    public function getUserRateForProjects($projectIds, $userId): ?array
    {
        $namedProjectIds = [];
        foreach ($projectIds as $projectId) {
            $namedProjectIds[$projectId] = self::INVOICES_RATE . self::SEPARATOR . $projectId;
        }

        $userRateForProjects = Property::select('name', 'value')
            ->where([
                    ['entity_id', '=', $userId],
                    ['entity_type', '=', Property::USER_CODE],
                ])
            ->whereIn('name', $namedProjectIds)
            ->get();

        foreach ($userRateForProjects as $userRateForProject) {
            $projectId = array_search($userRateForProject->name, $namedProjectIds);
            $answer[$projectId] = $userRateForProject->value ?? null;
        }

        return $answer ?? null;
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
            ->select('user_id', 'user_name', 'project_id', 'project_name')
            ->distinct()
            ->whereIn('user_id', $userIds)
            ->whereIn('project_id', $projectIds)
            ->groupBy('user_id', 'user_name', 'project_id', 'project_name')
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

        // If user do no have any projects we still have to show him and let him have a default rate on Invoices page
        foreach ($userIds as $userId) {
            if (!in_array($userId, array_keys($users))) {
                $user = User::where('id', '=', $userId)
                    ->first();

                $users[$userId] = [
                    'id' => $userId,
                    'full_name' => $user->full_name,
                    'projects' => [],
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
