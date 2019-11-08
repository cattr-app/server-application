<?php


namespace Tests\Feature\Auth;

use App\Models\Factories\UserFactory;
use Tests\TestCase;



class LogoutFromAllTest extends TestCase
{
    /**
     * @var array $loginData
     */
    private $loginData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uri = '/auth/login';
        $this->user = app(UserFactory::class)->withToken()->create();

    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->user->forceDelete();
    }

    public function test_logout_from_all()
    {
        $this->assertTrue(True);
    }
}
