<?php

namespace Modules\Invoices\Http\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Invoices\Entities\Repositories\InvoicesRepository;

/**
 * Class InvoicesController
 * @package Modules\Invoices\Http\Controllers
 */
class InvoicesController extends Controller
{
    /**
     * @var InvoicesRepository
     */
    private $invoicesRepository;

    /**
     * @var array $rates
     */
    private $rates = [];

    /**
     * InvoicesController constructor.
     * @param InvoicesRepository $invoicesRepository
     */
    public function __construct(InvoicesRepository $invoicesRepository)
    {
        $this->invoicesRepository = $invoicesRepository;

        parent::__construct();
    }

    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'index' => 'invoices.list',
            'update' => 'invoices.full_access',
            'projects' => 'invoices.list',
            'getDefaultRate' => 'invoices.list',
            'setDefaultRate' => 'invoices.full_access',
        ];
    }

    /**
     * Get invoices according userId->projectId
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $userIds = $request->input('userIds');
        $projectIds = $request->input('projectIds');

        $answer = [];
        foreach ($userIds as $userId) {
            $userProjectsRelations = \DB::table('project_report')
                ->select('user_id', 'project_id')
                ->distinct()
                ->where('user_id', $userId)
                ->whereIn('project_id', $projectIds)
                ->get();

            $this->rates = [];
            $projectIds = [];

            foreach ($userProjectsRelations as $userProjectsRelation) {
                $projectIds []= $userProjectsRelation->project_id;
            }

            $this->rates = $this->invoicesRepository->getUserRateForProjects($projectIds, $userId);

            $answer[] = [
                'userId' => $userId,
                'rates' => $this->rates
            ];
        }

        return response()->json($answer);
    }

    /**
     * Update or create rate for project according userId->projectId
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $userId = $request->input('userId');
        $projectId = $request->input('projectId');
        $rate = $request->input('rate');

        try {
            $answer = $this->invoicesRepository->updateOrCreateUserRate($userId, $projectId, $rate);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 500);
        }

        $answer["message"] = "New rate saved!";
        $answer["status"] = "success";
        return response()->json($answer);
    }

    /**
     * Get all project(-s) for user(-s)
     * @param Request $request
     * @return JsonResponse
     */
    public function projects(Request $request)
    {
        $user = Auth::user();

        $userIds = $request->input('userIds');
        $projectIds = $request->input('projectIds');

        $report = $this->invoicesRepository->getProjectsByUsers($userIds, $projectIds);
        return response()->json($report);
    }

    /**
     * Get default user rate
     * @param Request $request
     * @return JsonResponse
     */
    public function getDefaultRate(Request $request)
    {
        $user = Auth::user();

        $userIds = $request->input('userIds');

        $defaultRates = $this->invoicesRepository->getDefaultUsersRate($userIds);
        return response()->json($defaultRates);
    }

    /**
     * Update or create default rate for user
     * @param Request $request
     * @return JsonResponse
     */
    public function setDefaultRate(Request $request)
    {
        $user = Auth::user();

        $userId = $request->input('userId');
        $defaultRate = $request->input('defaultRate');

        try {
            $answer = $this->invoicesRepository->setDefaultRateForUser($userId, $defaultRate);
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage(),
                "status" => "error",
                'code' => $e->getCode(),
            ], 500);
        }

        $answer["message"] = "New default rate saved!";
        $answer["status"] = "success";
        return response()->json($answer);
    }
}
