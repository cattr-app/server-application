<?php

namespace App\Http\Requests\TaskActivity;

use App\Enums\ActivityType;
use App\Helpers\QueryHelper;
use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\Task;
use App\Http\Requests\CattrFormRequest;
use Illuminate\Validation\Rule;

class ShowTaskActivityRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('view', Task::find(request('task_id')));
    }

    public function _rules(): array
    {
        return array_merge(QueryHelper::getValidationRules(), [
            'page' => 'required|int',
            'task_id' => 'required|int|exists:tasks,id',
            'type' => ['required', Rule::enum(ActivityType::class)],
        ]);
    }
}
