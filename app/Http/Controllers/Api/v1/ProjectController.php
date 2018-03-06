<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Project;

class ProjectController extends ItemController
{
    function getItemClass()
    {
        return Project::class;
    }

    function getValidationRules()
    {
        return [
            'company_id'  => 'required',
            'name'        => 'required',
            'description' => 'required',
        ];
    }

    function getEventUniqueNamePart()
    {
        return 'project';
    }
}
