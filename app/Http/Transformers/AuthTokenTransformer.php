<?php

namespace App\Http\Transformers;

use Flugg\Responder\Transformers\Transformer;

class AuthTokenTransformer extends Transformer
{
    /**
     * @apiDefine AuthToken
     * @apiSuccess {String}    data.access_token  Token
     * @apiSuccess {String}    data.token_type    Token type
     * @apiSuccess {ISO8601}  [data.expires_in]   Token TTL
     * @apiSuccess {Object}    data.user          User Entity
     */
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
