<?php

namespace App\Http\Responses;

use Filter;
use Flugg\Responder\Serializers\SuccessSerializer;

class CattrSuccessResponse extends SuccessSerializer
{
    /**
     * @inheritDoc
     */
    public function collection($resourceKey, array $data): array
    {
        return ['data' => Filter::process(Filter::getSuccessResponseFilterName(), $data)];
    }

    /**
     * @inheritDoc
     */
    public function null(): array
    {
        return ['data' => Filter::process(Filter::getSuccessResponseFilterName(), null)];
    }

    /**
     * @inheritDoc
     */
    public function item($resourceKey, array $data): array
    {
        return ['data' => Filter::process(Filter::getSuccessResponseFilterName(), $data)];
    }
}
