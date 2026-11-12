<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class CsrfCookieTest extends TestCase
{
    // An absolute URL: TestCase prefixes relative paths with /api, this route lives outside of it
    private const URI = 'http://localhost/sanctum/csrf-cookie';

    public function test_guest_gets_an_empty_response_and_a_session_cookie(): void
    {
        $response = $this->get(self::URI);

        $response->assertNoContent();
        $this->assertSame('', $response->getContent());

        $cookie = $response->getCookie(config('session.cookie'), false);
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('/', $cookie->getPath());
    }

    public function test_the_endpoint_needs_no_token(): void
    {
        $this->withHeader('Authorization', 'Bearer invalid-token')->get(self::URI)->assertNoContent();
    }
}
