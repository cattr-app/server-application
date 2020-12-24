<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class BulkEditTest extends TestCase
{
    use WithFaker;

    private const URI = 'time-intervals/bulk-edit';

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

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->task = TaskFactory::refresh()->create();

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

    public function test_bulk_edit_as_admin(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->task->id);

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseMissing('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervals->toArray()];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertOk();

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }
    }

    public function test_bulk_edit_as_manager(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->task->id);

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseMissing('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervals->toArray()];

        $response = $this->actingAs($this->manager)->postJson(self::URI, $requestData);

        $response->assertForbidden();
    }

    public function test_bulk_edit_your_own_as_manager(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->task->id);

        foreach ($this->intervalsForManager as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervalsForManager->toArray()];

        $response = $this->actingAs($this->manager)->postJson(self::URI, $requestData);

        $response->assertOk();

        foreach ($this->intervalsForManager as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }
    }

    public function test_bulk_edit_as_auditor(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->task->id);

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseMissing('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervals->toArray()];

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $requestData);

        $response->assertForbidden();
    }

    public function test_bulk_edit_your_own_as_auditor(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->task->id);

        foreach ($this->intervalsForAuditor as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervalsForAuditor->toArray()];

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $requestData);

        $response->assertOk();

        foreach ($this->intervalsForAuditor as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }
    }

    public function test_bulk_edit_as_user(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->task->id);

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseMissing('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervals->toArray()];

        $response = $this->actingAs($this->user)->postJson(self::URI, $requestData);

        $response->assertForbidden();
    }

    public function test_bulk_edit_your_own_as_user(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->task->id);

        foreach ($this->intervalsForUser as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervalsForUser->toArray()];

        $response = $this->actingAs($this->user)->postJson(self::URI, $requestData);

        $response->assertOk();

        foreach ($this->intervalsForUser as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }
    }

    public function test_with_not_existing_intervals(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->task->id);

        $nonIntervals = [
            ['id' => TimeInterval::max('id') + 1, 'task_id' => $this->task->id],
            ['id' => TimeInterval::max('id') + 2, 'task_id' => $this->task->id]
        ];

        $requestData = ['intervals' => array_merge($this->intervals->toArray(), $nonIntervals)];

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseMissing('time_intervals', $interval->toArray());
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
