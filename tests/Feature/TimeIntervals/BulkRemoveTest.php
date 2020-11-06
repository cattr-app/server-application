<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class BulkRemoveTest extends TestCase
{
    private const URI = 'time-intervals/bulk-remove';

    private const INTERVALS_AMOUNT = 5;

    /** @var User $admin */
    private User $admin;
    /** @var User $manager */
    private User $manager;
    /** @var User $auditor */
    private User $auditor;
    /** @var User $user */
    private User $user;

    private Collection $intervals;
    private Collection $intervalsForManager;
    private Collection $intervalsForAuditor;
    private Collection $intervalsForUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->intervals = IntervalFactory::refresh()->withRandomRelations()->createMany(self::INTERVALS_AMOUNT);
        $this->intervalsForManager = IntervalFactory::refresh()
            ->forUser($this->manager)
            ->createMany(self::INTERVALS_AMOUNT);
        $this->intervalsForAuditor = IntervalFactory::refresh()
            ->forUser($this->auditor)
            ->createMany(self::INTERVALS_AMOUNT);
        $this->intervalsForUser = IntervalFactory::refresh()
            ->forUser($this->user)
            ->createMany(self::INTERVALS_AMOUNT);
    }

    public function test_bulk_remove_as_admin(): void
    {
        foreach ($this->intervals as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervals->pluck('id')->toArray()];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertOk();

        foreach ($this->intervals as $interval) {
            $this->assertSoftDeleted('time_intervals', $interval->toArray());
        }
    }

    public function test_bulk_remove_as_manager(): void
    {
        foreach ($this->intervals as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervals->pluck('id')->toArray()];

        $response = $this->actingAs($this->manager)->postJson(self::URI, $requestData);

        $response->assertForbidden();
    }

    public function test_bulk_remove_your_own_as_manager(): void
    {
        foreach ($this->intervalsForManager as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervalsForManager->pluck('id')->toArray()];

        $response = $this->actingAs($this->manager)->postJson(self::URI, $requestData);

        $response->assertOk();

        foreach ($this->intervalsForManager as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }
    }

    public function test_bulk_remove_as_user(): void
    {
        foreach ($this->intervals as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervals->pluck('id')->toArray()];

        $response = $this->actingAs($this->user)->postJson(self::URI, $requestData);

        $response->assertForbidden();
    }

    public function test_bulk_remove_your_own_as_user(): void
    {
        foreach ($this->intervalsForUser as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervalsForUser->pluck('id')->toArray()];

        $response = $this->actingAs($this->user)->postJson(self::URI, $requestData);

        $response->assertOk();

        foreach ($this->intervalsForUser as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }
    }

    public function test_bulk_remove_your_own_as_auditor(): void
    {
        foreach ($this->intervalsForAuditor as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervalsForAuditor->pluck('id')->toArray()];

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $requestData);

        $response->assertOk();

        foreach ($this->intervalsForAuditor as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }
    }

    public function test_with_not_existing_intervals(): void
    {
        $nonIntervals = [TimeInterval::max('id') + 1, TimeInterval::max('id') + 2];

        $requestData = ['intervals' => array_merge($this->intervals->pluck('id')->toArray(), $nonIntervals)];

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertValidationError();
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
