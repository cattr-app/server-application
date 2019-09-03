<?php

use Illuminate\Encryption\Encrypter;

return [
    'name' => 'RedmineIntegration',

    'request' => [
        'signature' => env('REQUEST_SIGNATURE', 'DEFAULT')
    ]
];
