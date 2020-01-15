<?php

namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\User;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'v1/time-intervals/list';
    private const COUNT_INTERVALS = 10;

    /**
     * @var User
     */
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        IntervalFactory::createMany(self::COUNT_INTERVALS); //создавать через сreate()
    }

    public function test_list()
    {
        $response = $this->actingAs($this->admin)->get(self::URI);

        $response->assertOk();
        $response->assertJson(TimeInterval::all()->toArray());
    }

    public function test_unauthorized()
    {
        $response = $this->get(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params()
    {
        $response = $this->actingAs($this->admin)->get(self::URI);

        $response->assertValidationError();
    }
}
