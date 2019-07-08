<?php

namespace Tests;

use DB;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUp();
        DB::beginTransaction();
    }

    /**
     * @throws \Exception
     */
    public function tearDown()
    {
        DB::rollback();
        parent::tearDown();

    }


    public function getAdminToken()
    {
        $auth = [
            'login'     => 'admin@example.com',
            'password'  => 'admin'
        ];

        $response = $this->postJson('/auth/login', $auth);

        return $response->json('access_token');
    }
}
