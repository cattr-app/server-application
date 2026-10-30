<?php

namespace Tests\Feature\TimeIntervals;

use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private const URI = 'time-intervals/create';

    private User $admin;
    private Task $task;

    private array $intervalData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->task = TaskFactory::forUser($this->admin)->create();

        $this->intervalData = IntervalFactory::createRandomModelDataWithRelation();
        $this->intervalData['user_id'] = $this->admin->id;
    }

    public function test_create(): void
    {
        $this->assertDatabaseMissing('time_intervals', $this->intervalData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->intervalData);

        $response->assertOk();
        $this->assertDatabaseHas('time_intervals', $this->intervalData);
    }

    public function test_already_exists_left(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 00:55:00';
        $newInterval['end_at'] = '1900-01-01 01:05:00';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists_right(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 01:00:00';
        $newInterval['end_at'] = '1900-01-01 01:10:00';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists_left_inner(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 00:55:00';
        $newInterval['end_at'] = '1900-01-01 01:03:00';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists_right_inner(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 01:03:00';
        $newInterval['end_at'] = '1900-01-01 01:10:00';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists_inner(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 01:01:00';
        $newInterval['end_at'] = '1900-01-01 01:03:00';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists_outer(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 00:55:00';
        $newInterval['end_at'] = '1900-01-01 01:10:00';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists(): void
    {
        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->intervalData);

        $response->assertValidationError();
    }


    public function test_manager_can_create_manual_time_for_assigned_user(): void
    {
        $manager = UserFactory::asManager()->create();
        $manager->manual_time = true;
        $manager->save();

        $targetUser = UserFactory::asUser()->create();
        $task = TaskFactory::forUser($targetUser)->create();
        $data = [
            ...$this->intervalData,
            'user_id' => $targetUser->id,
            'task_id' => $task->id,
            'is_manual' => true,
        ];

        $response = $this->actingAs($manager)->postJson(self::URI, $data);

        $response->assertOk();
        $this->assertDatabaseHas('time_intervals', [
            'task_id' => $task->id,
            'user_id' => $targetUser->id,
            'is_manual' => true,
        ]);
    }

    public function test_manager_cannot_create_manual_time_for_unassigned_user(): void
    {
        $manager = UserFactory::asManager()->create();
        $manager->manual_time = true;
        $manager->save();

        $targetUser = UserFactory::asUser()->create();
        $otherUser = UserFactory::asUser()->create();
        $task = TaskFactory::forUser($otherUser)->create();
        $data = [
            ...$this->intervalData,
            'user_id' => $targetUser->id,
            'task_id' => $task->id,
            'is_manual' => true,
        ];

        $response = $this->actingAs($manager)->postJson(self::URI, $data);

        $response->assertForbidden();
        $this->assertDatabaseMissing('time_intervals', [
            'task_id' => $task->id,
            'user_id' => $targetUser->id,
        ]);
    }

    public function test_create_manual_interval_from_form_payload(): void
    {
        $data = [
            'user_id' => $this->admin->id,
            'task_id' => $this->task->id,
            'timezone' => 'Europe/Moscow',
            'start_at' => '2000-01-01T23:30:00.000Z',
            'end_at' => '2000-01-02T00:25:00.000Z',
            'is_manual' => true,
        ];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $data);

        $response->assertOk();
        $this->assertDatabaseHas('time_intervals', [
            'user_id' => $this->admin->id,
            'task_id' => $this->task->id,
            'is_manual' => true,
        ]);
    }

    public function test_end_before_start(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 00:50:00';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->intervalData);

        $response->assertValidationError();
        $this->assertDatabaseMissing('time_intervals', $this->intervalData);
    }

    public function test_end_equals_start(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:00:00';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->intervalData);

        $response->assertValidationError();
        $this->assertDatabaseMissing('time_intervals', $this->intervalData);
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
