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
}
