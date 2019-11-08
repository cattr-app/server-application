<?php

namespace Tests\Feature\Auth\PasswordReset;

use Tests\TestCase;

class SendTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->uri = '/auth/password-reset/send';
    }

    public function test_without_params()
    {
        $response = $this->post($this->uri);
        $response->assertStatus(404);

    }
}
