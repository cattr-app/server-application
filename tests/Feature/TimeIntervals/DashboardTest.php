<?php

namespace Tests\Feature\TimeIntervals;

use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class DashboardTest
 */
class DashboardTest extends TestCase
{
    private const URI = 'v1/time-intervals/dashboard';

    private const INTERVALS_AMOUNT = 2;

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

        $this->intervals = IntervalFactory::forUser($this->admin)->createMany(self::INTERVALS_AMOUNT);
    }

    public function test_dashboard(): void
    {
        $requestData = [
            'start_at' => $this->intervals->min('start_at'),
            'end_at' => Carbon::create($this->intervals->max('start_at'))->addHour(),
            'user_ids' => [$this->admin->id]
        ];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertOk();
        $this->assertEquals(
            $this->intervals->count(),
            count($response->json('userIntervals')[$this->admin->id]['intervals'])
        );

        #TODO change later
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertValidationError();
    }
}
