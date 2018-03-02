<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Project;

class ProjectController extends ItemController
{
    function getItemClass()
    {
        return Project::class;
    }
}
