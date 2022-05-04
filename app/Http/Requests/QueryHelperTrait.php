<?php

namespace App\Http\Requests;

trait QueryHelperTrait
{
    public function helperRules(): array
    {
        return [
            'limit' => 'sometimes|int',
            'offset' => 'sometimes|int',
            'orderBy' => 'sometimes|string',
            'with.*' => 'sometimes|string',
            'withCount.*' => 'sometimes|string',
            'search.query.*' => 'sometimes|string',
            'search.fields.*' => 'sometimes|string',
        ];
    }
}
