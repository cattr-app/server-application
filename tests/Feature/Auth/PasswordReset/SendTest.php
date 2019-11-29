<?php

namespace Tests\Feature\Auth\PasswordReset;

use Tests\TestCase;

class SendTest extends TestCase
{
    const URI = 'auth/password-reset/send';

    public function test_without_params()
    {
        $response = $this->postJson(self::URI);
        $response->assertStatus(404);
    }

    //TODO SendTest
}
