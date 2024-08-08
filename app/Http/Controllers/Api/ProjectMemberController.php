<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectMember\BulkEditProjectMemberRequest;
use App\Http\Requests\ProjectMember\ShowProjectMemberRequest;
use App\Services\ProjectMemberService;
use CatEvent;
use Filter;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ProjectMemberController extends Controller
{
    /**
     * @api             {post} /project-members/list List Project Members
     * @apiDescription  Get list of project members
     *
     * @apiVersion      4.0.0
     * @apiName         ListProjectMembers
     * @apiGroup        ProjectMember
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer} project_id  ID of the project
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "project_id": 1
     *  }
     *
     * @apiSuccess {Integer}  id                Project ID
     * @apiSuccess {Object[]} users             List of users
     * @apiSuccess {Integer}  users.id          User ID
     * @apiSuccess {String}   users.full_name   User full name
     * @apiSuccess {String}   users.email       User email
     * @apiSuccess {String}   users.url         User URL
     * @apiSuccess {Integer}  users.company_id  Company ID
     * @apiSuccess {String}   users.avatar      User avatar
     * @apiSuccess {Integer}  users.screenshots_active  Screenshots active status
     * @apiSuccess {Integer}  users.manual_time  Manual time status
     * @apiSuccess {Integer}  users.computer_time_popup  Computer time popup interval
     * @apiSuccess {Boolean}  users.blur_screenshots  Blur screenshots status
     * @apiSuccess {Boolean}  users.web_and_app_monitoring  Web and app monitoring status
     * @apiSuccess {Integer}  users.screenshots_interval  Screenshots interval
     * @apiSuccess {Boolean}  users.active  User active status
     * @apiSuccess {String}   users.deleted_at  Deletion timestamp
     * @apiSuccess {String}   users.created_at  Creation timestamp
     * @apiSuccess {String}   users.updated_at  Last update timestamp
     * @apiSuccess {String}   users.timezone  User timezone
     * @apiSuccess {Boolean}  users.important  User importance status
     * @apiSuccess {Boolean}  users.change_password  Change password status
     * @apiSuccess {Integer}  users.role_id  User role ID
     * @apiSuccess {String}   users.user_language  User language
     * @apiSuccess {String}   users.type  User type
     * @apiSuccess {Boolean}  users.invitation_sent  Invitation sent status
     * @apiSuccess {Integer}  users.nonce  User nonce
     * @apiSuccess {Boolean}  users.client_installed  Client installed status
     * @apiSuccess {Boolean}  users.permanent_screenshots  Permanent screenshots status
     * @apiSuccess {String}   users.last_activity  Last activity timestamp
     * @apiSuccess {Boolean}  users.online  Online status
     * @apiSuccess {Boolean}  users.can_view_team_tab  Can view team tab status
     * @apiSuccess {Boolean}  users.can_create_task  Can create task status
     * @apiSuccess {Object}   users.pivot  Pivot data
     * @apiSuccess {Integer}  users.pivot.project_id  Project ID in pivot
     * @apiSuccess {Integer}  users.pivot.user_id  User ID in pivot
     * @apiSuccess {Integer}  users.pivot.role_id  Role ID in pivot
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *      "id": 1,
     *      "users": [
     *        {
     *          "id": 1,
     *          "full_name": "Admin",
     *          "email": "admin@cattr.app",
     *          "url": "",
     *          "company_id": 1,
     *          "avatar": "",
     *          "screenshots_active": 1,
     *          "manual_time": 0,
     *          "computer_time_popup": 300,
     *          "blur_screenshots": false,
     *          "web_and_app_monitoring": true,
     *          "screenshots_interval": 5,
     *          "active": 1,
     *          "deleted_at": null,
     *          "created_at": "2023-10-26T10:26:17.000000Z",
     *          "updated_at": "2024-02-15T19:06:42.000000Z",
     *          "timezone": null,
     *          "important": 0,
     *          "change_password": 0,
     *          "role_id": 0,
     *          "user_language": "en",
     *          "type": "employee",
     *          "invitation_sent": false,
     *          "nonce": 0,
     *          "client_installed": 0,
     *          "permanent_screenshots": 0,
     *          "last_activity": "2023-10-26 10:26:17",
     *          "online": false,
     *          "can_view_team_tab": true,
     *          "can_create_task": true,
     *          "pivot": {
     *            "project_id": 1,
     *            "user_id": 1,
     *            "role_id": 2
     *          }
     *        },
     *        {
     *          "id": 2,
     *          "full_name": "Fabiola Mertz",
     *          "email": "projectManager@example.com",
     *          "url": "",
     *          "company_id": 1,
     *          "avatar": "",
     *          "screenshots_active": 1,
     *          "manual_time": 0,
     *          "computer_time_popup": 300,
     *          "blur_screenshots": false,
     *          "web_and_app_monitoring": true,
     *          "screenshots_interval": 5,
     *          "active": 1,
     *          "deleted_at": null,
     *          "created_at": "2023-10-26T10:26:17.000000Z",
     *          "updated_at": "2023-10-26T10:26:17.000000Z",
     *          "timezone": null,
     *          "important": 0,
     *          "change_password": 0,
     *          "role_id": 2,
     *          "user_language": "en",
     *          "type": "employee",
     *          "invitation_sent": false,
     *          "nonce": 0,
     *          "client_installed": 0,
     *          "permanent_screenshots": 0,
     *          "last_activity": "2023-10-26 09:44:17",
     *          "online": false,
     *          "can_view_team_tab": false,
     *          "can_create_task": false,
     *          "pivot": {
     *            "project_id": 1,
     *            "user_id": 2,
     *            "role_id": 2
     *          }
     *        },
     *        ...
     *      ]
     *    }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     */
    /**
     *
     * @param ShowProjectMemberRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function list(ShowProjectMemberRequest $request): JsonResponse
    {
        $data = $request->validated();

        throw_unless($data, ValidationException::withMessages([]));

        $projectMembers = ProjectMemberService::getMembers($data['project_id']);

        $projectMembers['users'] = $projectMembers['users'] ?? [];

        return responder()->success($projectMembers)->respond();
    }

    /**
     * @api             {post} /api/project-members/bulk-edit Bulk Edit Project Members
     * @apiDescription  Edit roles of multiple project members
     *
     * @apiVersion      4.0.0
     * @apiName         BulkEditProjectMembers
     * @apiGroup        ProjectMember
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer}        project_id    Project ID
     * @apiParam {Object[]}       user_roles    Array of user roles
     * @apiParam {Integer}        user_roles.user_id  User ID
     * @apiParam {Integer}        user_roles.role_id  Role ID
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "project_id": 1,
     *    "user_roles": [
     *      {
     *        "user_id": 1,
     *        "role_id": 2
     *      },
     *      {
     *        "user_id": 2,
     *        "role_id": 3
     *      }
     *    ]
     *  }
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 204 No Content
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     */
    /**
     * @param BulkEditProjectMemberRequest $request
     * @return JsonResponse
     */
    public function bulkEdit(BulkEditProjectMemberRequest $request): JsonResponse
    {
        $data = Filter::process(Filter::getRequestFilterName(), $request->validated());

        $userRoles = [];

        foreach ($data['user_roles'] as $value) {
            $userRoles[$value['user_id']] = ['role_id' => $value['role_id']];
        }

        CatEvent::dispatch(Filter::getBeforeActionEventName(), [$data['project_id'], $userRoles]);

        ProjectMemberService::syncMembers($data['project_id'], $userRoles);

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$data['project_id'], $userRoles]);

        return responder()->success()->respond(204);
    }
}
