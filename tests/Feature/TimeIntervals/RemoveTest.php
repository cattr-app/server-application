<?php


namespace Tests\Feature\TimeIntervals;


use App\Models\TimeInterval;
use App\User;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    private const URI = 'v1/time-intervals/remove';

    /**
     * @var TimeInterval
     */
    private $interval;

    /**
     * @var User
     */
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->interval = IntervalFactory::create();
    }

    public function test_remove()
    {
        $this->assertDatabaseHas('time_intervals', $this->interval->toArray());

        $response = $this->actingAs($this->admin)->postJson(self::URI, ['id' => $this->interval->id]);

        $response->assertSuccess();
        $this->assertSoftDeleted('time_intervals', ['id' =>$this->interval->id]);
    }

    public function test_unauthorized()
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
