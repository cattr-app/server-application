<?php

namespace App\Http\Responses;

use Filter;
use Flugg\Responder\Contracts\ErrorSerializer;

class CattrErrorResponse implements ErrorSerializer
{
    public function format($errorCode = null, string $message = null, array $data = null): array
    {
        $response = [
            'error' => [
                'code' => $errorCode,
                'message' => $message,
            ],
        ];

        $data = Filter::process(Filter::getErrorResponseFilterName(), $data);

        if (is_array($data)) {
            $response['error'] = array_merge($response['error'], $data);
        }

        return $response;
    }
}
