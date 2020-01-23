<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * @method TestResponse get($uri, array $headers = [])
 * @method TestResponse getJson($uri, array $headers = [])
 * @method TestResponse post($uri, array $data = [], array $headers = [])
 * @method TestResponse postJson($uri, array $data = [], array $headers = [])
 * @method TestResponse put($uri, array $data = [], array $headers = [])
 * @method TestResponse putJson($uri, array $data = [], array $headers = [])
 * @method TestResponse patch($uri, array $data = [], array $headers = [])
 * @method TestResponse patchJson($uri, array $data = [], array $headers = [])
 * @method TestResponse delete($uri, array $data = [], array $headers = [])
 * @method TestResponse deleteJson($uri, array $data = [], array $headers = [])
 * @method TestResponse json($method, $uri, array $data = [], array $headers = [])
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;

    /**
     * @param UserContract $user
     * @param null $driver
     * @return $this|BaseTestCase
     */
    public function actingAs(UserContract $user, $driver = null)
    {
        /** @var User $user */
        $token = $user->tokens()->first()->token;
        $this->withHeader('Authorization', 'Bearer ' . $token);

        return $this;
    }

    /**
     * @param $response
     * @return TestResponse
     */
    protected function createTestResponse($response): TestResponse
    {
        return TestResponse::fromBaseResponse($response);
    }
}
