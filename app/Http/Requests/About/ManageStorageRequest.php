<?php

namespace App\Http\Requests\About;

use App\Http\Requests\CattrFormRequest;

class ManageStorageRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function _rules(): array
    {
        return [];
    }
}
