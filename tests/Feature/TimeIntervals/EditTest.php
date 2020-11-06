<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class EditTest extends TestCase
{
    private const URI = 'time-intervals/edit';

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
    /** @var TimeInterval $timeIntervalForManager */
    private TimeInterval $timeIntervalForManager;
    /** @var TimeInterval $timeIntervalForAuditor */
    private TimeInterval $timeIntervalForAuditor;
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
        $this->timeIntervalForManager = IntervalFactory::forUser($this->manager)->create();
        $this->timeIntervalForAuditor = IntervalFactory::forUser($this->auditor)->create();
        $this->timeIntervalForUser = IntervalFactory::forUser($this->user)->create();
    }

    public function test_edit_as_admin(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $editedInterval = clone $this->timeInterval;
        $editedInterval->user_id = UserFactory::refresh()->asUser()->create()->id;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $editedInterval->toArray());

        $response->assertOk();
        $this->assertDatabaseHas('time_intervals', $editedInterval->toArray());
    }

    public function test_edit_as_manager(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $editedInterval = clone $this->timeInterval;
        $editedInterval->user_id = UserFactory::refresh()->asUser()->create()->id;

        $response = $this->actingAs($this->manager)->postJson(self::URI, $editedInterval->toArray());
        $response->assertForbidden();
    }

    public function test_edit_your_own_as_manager(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $editedInterval = clone $this->timeIntervalForManager;
        $editedInterval->user_id = UserFactory::refresh()->asUser()->create()->id;

        $response = $this->actingAs($this->manager)->postJson(self::URI, $editedInterval->toArray());

        $response->assertOk();
        $this->assertDatabaseHas('time_intervals', $editedInterval->toArray());
    }

    public function test_edit_as_auditor(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $editedInterval = clone $this->timeInterval;
        $editedInterval->user_id = UserFactory::refresh()->asUser()->create()->id;

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $editedInterval->toArray());

        $response->assertForbidden();
    }

    public function test_edit_your_own_as_auditor(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeIntervalForAuditor->toArray());

        $editedInterval = clone $this->timeIntervalForAuditor;
        $editedInterval->user_id = UserFactory::refresh()->asUser()->create()->id;

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $editedInterval->toArray());

        $response->assertOk();
        $this->assertDatabaseHas('time_intervals', $editedInterval->toArray());
    }

    public function test_edit_as_user(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $editedInterval = clone $this->timeInterval;
        $editedInterval->user_id = UserFactory::refresh()->asUser()->create()->id;

        $response = $this->actingAs($this->user)->postJson(self::URI, $editedInterval->toArray());

        $response->assertForbidden();
    }

    public function test_edit_your_own_as_user(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeIntervalForManager->toArray());

        $editedInterval = clone $this->timeIntervalForUser;
        $editedInterval->user_id = UserFactory::refresh()->asUser()->create()->id;

        $response = $this
            ->actingAs($this->user)
            ->postJson(self::URI, $editedInterval->toArray());

        $response->assertOk();
        $this->assertDatabaseHas('time_intervals', $editedInterval->toArray());
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
