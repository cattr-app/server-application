<?php

namespace Tests\Feature\Auth\PasswordReset;

use Tests\TestCase;

class ProcessTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->uri = 'auth/password-reset/process';
    }

    public function test_without_params()
    {
        $response = $this->postJson($this->uri);
        $response->assertStatus(400);

    }
}
