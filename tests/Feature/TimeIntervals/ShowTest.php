<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\User;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = 'v1/time-intervals/show';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var TimeInterval
     */
    private $interval;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->interval = IntervalFactory::create();
    }

    public function test_show()
    {
        $this->assertDatabaseHas('time_intervals', $this->interval->toArray());

        $response = $this->actingAs($this->admin)->postJson(self::URI, ['id' => $this->interval->id]);
        $response->assertOk();

        $response->assertJson($this->interval->toArray());
    }

    public function test_unauthorized()
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params()
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertValidationError();
    }
}
