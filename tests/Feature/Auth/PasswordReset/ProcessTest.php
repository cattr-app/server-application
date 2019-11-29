<?php

namespace Tests\Feature\Auth\PasswordReset;

use Tests\TestCase;

class ProcessTest extends TestCase
{
    const URI = 'auth/password-reset/process';

    public function test_without_params()
    {
        $response = $this->postJson(self::URI);
        $response->assertStatus(400);

    }

    //TODO ProcessTest
}
