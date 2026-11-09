<?php

namespace App\Http\Transformers;

use Flugg\Responder\Transformers\Transformer;

class AuthTokenTransformer extends Transformer
{
    public function transform(array $input): array
    {
        return [
            'access_token' => $input['token'],
            'token_type' => $input['type'] ?? 'bearer',
            'expires_in' => $input['expires'] ?? null,
            'user' => auth()->user(),
        ];
    }
}
