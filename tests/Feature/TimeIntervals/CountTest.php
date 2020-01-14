<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\User;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CountTest extends TestCase
{
    private const URI = 'v1/time-intervals/count';

    private const SCREENSHOTS_AMOUNT = 10;

    /**
     * @var User
     */
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        IntervalFactory::createMany(self::SCREENSHOTS_AMOUNT);
    }

    public function test_count()
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson(['total' => TimeInterval::count()]);
    }

    public function test_unauthorized()
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
