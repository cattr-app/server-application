<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    private const URI = 'time-intervals/remove';

    private TimeInterval $interval;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->interval = IntervalFactory::create();
    }

    public function test_remove(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->interval->toArray());

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->interval->only('id'));

        $response->assertSuccess();
        $this->assertSoftDeleted('time_intervals', ['id' =>$this->interval->id]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
