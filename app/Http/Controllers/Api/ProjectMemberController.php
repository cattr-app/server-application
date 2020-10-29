<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectMember\BulkEditProjectMemberRequest;
use App\Http\Requests\ProjectMember\ShowProjectMemberRequest;
use App\Services\ProjectMemberService;
use Illuminate\Http\JsonResponse;

class ProjectMemberController extends Controller
{
    protected ProjectMemberService $projectMemberService;

    /**
     * ProjectMemberController constructor.
     * @param ProjectMemberService $projectMemberService
     */
    public function __construct(ProjectMemberService $projectMemberService)
    {
        $this->projectMemberService = $projectMemberService;
    }

    /**
     * @param ShowProjectMemberRequest $request
     * @return JsonResponse
     */
    public function show(ShowProjectMemberRequest $request): JsonResponse
    {
        $data = $request->validated();
        $projectMembers = $this->projectMemberService->getMembers($data['project_id']);

        return new JsonResponse([
            'success' => true,
            'data' => $projectMembers,
        ]);
    }

    /**
     * @param BulkEditProjectMemberRequest $request
     * @return JsonResponse
     */
    public function bulkEdit(BulkEditProjectMemberRequest $request): JsonResponse
    {
        $data = $request->validated();

        $userRoles = [];

        foreach ($data['user_roles'] as $key => $value) {
            $userRoles[$value['user_id']] = ['role_id' => $value['role_id']];
        }

        $this->projectMemberService->syncMembers($data['project_id'], $userRoles);

        return new JsonResponse([
            'success' => true,
        ]);
    }
}
