<?php

namespace Tests\Feature\Interval;

use App\User;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    private const URI = 'v1/time-intervals/dashboard';
    private const COUNT_INTERVALS = 10;

    /**
     * @var Collection
     */
    private $intervals;

    /**
     * @var User
     */
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->intervals = IntervalFactory::createMany(self::COUNT_INTERVALS);
    }

    public function test_dashboard()
    {
        $faker = FakerFactory::create();

        $interval = $this->intervals[0];

        $startTimestamp = $faker->dateTime($interval->start_at)->getTimestamp() - 99999;

        $requestData = [
            'start_at' => date('Y-m-d H:i:s', $startTimestamp),
            'end_at' => date('Y-m-d H:i:s', time()),
            'user_ids' => [$interval->user_id]
        ];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);
        $response->assertOk();
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
