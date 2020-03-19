<?php

namespace Modules\Invoices\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Models\UserDefaultRate;

class InvoicesController extends Controller
{
    /**
     * Get invoices according userId->projectId
     */
    public function index(Request $request): JsonResponse
    {
        $validationRules = [
            'userIds.*' => 'int',
            'projectIds.*' => 'int',
        ];

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return new JsonResponse(
                [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ],
                400
            );
        }

        $userIds = $request->input('userIds', []);
        $projectIds = $request->input('projectIds', []);

        $answer = [];
        $projects = Project::whereIn('id', $projectIds)->get();
        $invoices = Invoices::whereIn('user_id', $userIds)->whereIn('project_id', $projectIds)->get();
        $users = User::without(['role', 'projectsRelation'])->whereIn('id', $userIds)->get();
        $userRates = UserDefaultRate::whereIn('user_id', $users->pluck('id'))->get();

        foreach ($users as $user) {
            $defaultRate = $userRates->where('user_id', $user->id)->first()->default_rate ?? UserDefaultRate::ZERO_RATE;

            $projectsRates = $invoices->where('user_id', $user->id);
            foreach ($projects as $projectWithRate) {
                $projectRate = $projectsRates->firstWhere('project_id', '=', $projectWithRate['id']);
                $projectWithRate->rate = $projectRate ? $projectRate->rate : $defaultRate;
            }


            $answer[$user->id] = [
                'user' => $user,
                'default_rate' => $defaultRate,
                'projects' => $projects->toArray()
            ];
        }

        return new JsonResponse(collect($answer));
    }

    /**
     * Update or create rate for project according userId->projectId
     */
    public function setProjectRate(Request $request): JsonResponse
    {
        $validationRules = [
            'userIds.*' => 'int',
            'projectIds.*' => 'int',
            'rate' => 'required|regex:/^\d+(\.\d{1,2})?$/',
        ];

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return new JsonResponse(
                [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ],
                400
            );
        }

        $userId = $request->input('userId');
        $projectId = $request->input('projectId');
        $rate = $request->input('rate');

        $answer = Invoices::updateOrCreate(['user_id' => $userId, 'project_id' => $projectId], ['rate' => (string)$rate]);

        return new JsonResponse([
            'message' => 'Rate successfully update for project!',
            'status' => 'success',
            $answer
        ]);
    }

    /**
     * Update or create default rate for user
     */
    public function setDefaultRate(Request $request): JsonResponse
    {
        $validationRules = [
            'userId' => 'int',
            'defaultRate' => 'required|regex:/^\d+(\.\d{1,2})?$/',
        ];

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return new JsonResponse(
                [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ],
                400
            );
        }

        $userId = $request->input('userId');
        $defaultRate = $request->input('defaultRate');

        $answer = UserDefaultRate::updateOrCreate(['user_id' => $userId], ['default_rate' => (string)$defaultRate]);

        return new JsonResponse([
            'message' => 'New default rate saved successfully!',
            'status' => 'success',
            $answer
        ]);
    }
}
