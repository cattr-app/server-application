<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\ProjectsUsers;
use Illuminate\Http\Request;

/**
 * Class ProjectsUsersController
 *
 * @package App\Http\Controllers\Api\v1
 */
class ProjectsUsersController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return ProjectsUsers::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'project_id' => 'required',
            'user_id'    => 'required',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'projects-users';
    }

    /**
     * @api {post} /api/v1/projects-users/list List
     * @apiDescription Get list of ProjectUser
     * @apiVersion 0.1.0
     * @apiName GetProjectUserList
     * @apiGroup ProjectUser
     *
     * @apiParam {Integer}  [project_id] `QueryParam` Project ID
     * @apiParam {Integer}  [user_id]    `QueryParam` User ID
     * @apiParam {DateTime} [created_at] `QueryParam` Creation DateTime
     * @apiParam {DateTime} [updated_at] `QueryParam` Last update DataTime
     *
     * @apiSuccess (200) {Object} Project User object
     */

    /**
     * @api {post} /api/v1/projects-users/create Create
     * @apiDescription Create ProjectUser
     * @apiVersion 0.1.0
     * @apiName CreateProjectUser
     * @apiGroup ProjectUser
     */

    /**
     * @api {post} /api/v1/projects-users/show Show
     * @apiDescription Show ProjectUser
     * @apiVersion 0.1.0
     * @apiName ShowProjectUser
     * @apiGroup ProjectUser
     */

    /**
     * @api {post} /api/v1/projects-users/edit Edit
     * @apiDescription Edit ProjectUser
     * @apiVersion 0.1.0
     * @apiName EditProjectUser
     * @apiGroup ProjectUser
     */

    /**
     * @api {post} /api/v1/projects-users/destroy Destroy
     * @apiDescription Destroy Project
     * @apiVersion 0.1.0
     * @apiName DestroyProjectUser
     * @apiGroup ProjectUser
     */

}
