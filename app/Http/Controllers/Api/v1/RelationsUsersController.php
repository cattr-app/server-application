<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\RelationsUsers;
use Illuminate\Http\Request;

class RelationsUsersController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return RelationsUsers::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'user_id'          => 'required',
            'attached_user_id' => 'required',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'attached-users';
    }
}
