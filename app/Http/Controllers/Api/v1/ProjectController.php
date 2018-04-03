<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Project;

/**
 * Class ProjectController
 *
 * @package App\Http\Controllers\Api\v1
 */
class ProjectController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return Project::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'company_id'  => 'required',
            'name'        => 'required',
            'description' => 'required',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'project';
    }

    /**
     * @api {post} /api/v1/projects/list List
     * @apiDescription Get list of Projects
     * @apiVersion 0.1.0
     * @apiName GetProjectList
     * @apiGroup Project
     *
     * @apiParam {Integer} [id] `QueryParam` Project ID
     * @apiParam {String} [name] `QueryParam` Project Name
     * @apiParam {String} [description] `QueryParam` Project Description
     * @apiParam {DateTime} [created_at] `QueryParam` Project Creation DateTime
     * @apiParam {DateTime} [updated_at] `QueryParam` Last Project update DataTime
     * @apiParam {DateTime} [deleted_at] `QueryParam` When Project was deleted (null if not)
     *
     * @apiSuccess (200) {Project[]} ProjectList array of Project objects
     */

    /**
     * @api {post} /api/v1/projects/create Create
     * @apiDescription Create Project
     * @apiVersion 0.1.0
     * @apiName CreateProject
     * @apiGroup Project
     */

    /**
     * @api {post} /api/v1/projects/show Show
     * @apiDescription Show Project
     * @apiVersion 0.1.0
     * @apiName ShowProject
     * @apiGroup Project
     */

    /**
     * @api {post} /api/v1/projects/edit Edit
     * @apiDescription Edit Project
     * @apiVersion 0.1.0
     * @apiName EditProject
     * @apiGroup Project
     */

    /**
     * @api {post} /api/v1/projects/destroy Destroy
     * @apiDescription Destroy Project
     * @apiVersion 0.1.0
     * @apiName DestroyProject
     * @apiGroup Project
     */
}
