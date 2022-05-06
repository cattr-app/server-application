<?php

namespace App\Http\Requests\Task;

use App\Helpers\QueryHelper;
use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\Task;
use App\Http\Requests\CattrFormRequest;

class ShowTaskRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('view', Task::find(request('id')));
    }

    public function _rules(): array
    {
        return array_merge(QueryHelper::getValidationRules(), [
            'id' => 'required|int',
        ]);
    }
}
