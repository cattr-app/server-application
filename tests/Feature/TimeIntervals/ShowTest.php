<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = 'time-intervals/show';

    /** @var User $admin */
    private User $admin;
    /** @var User $manager */
    private User $manager;
    /** @var User $auditor */
    private User $auditor;
    /** @var User $user */
    private User $user;

    /** @var TimeInterval $timeInterval */
    private TimeInterval $timeInterval;
    /** @var TimeInterval $timeIntervalForUser */
    private TimeInterval $timeIntervalForUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->timeInterval = IntervalFactory::create();
        $this->timeIntervalForUser = IntervalFactory::forUser($this->user)->create();
    }

    public function test_show_as_admin(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->timeInterval->only('id'));
        $response->assertOk();

        $response->assertJson($this->timeInterval->toArray());
    }

    public function test_show_as_manager(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->timeInterval->only('id'));
        $response->assertOk();

        $response->assertJson($this->timeInterval->toArray());
    }

    public function test_show_as_auditor(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->timeInterval->only('id'));
        $response->assertOk();

        $response->assertJson($this->timeInterval->toArray());
    }

    public function test_show_as_user(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $response = $this->actingAs($this->user)->postJson(self::URI, $this->timeInterval->only('id'));

        $response->assertForbidden();
    }

    public function test_show_your_own_as_user(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeIntervalForUser->toArray());

        $response = $this
            ->actingAs($this->user)
            ->postJson(self::URI, $this->timeIntervalForUser->only('id'));

        $response->assertJson($this->timeIntervalForUser->toArray());
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
