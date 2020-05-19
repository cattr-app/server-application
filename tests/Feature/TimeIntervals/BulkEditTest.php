<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class BulkEditTest extends TestCase
{
    private const URI = 'v1/time-intervals/bulk-edit';

    private const INTERVALS_AMOUNT = 5;

    private User $admin;
    private Collection $intervals;
    private Task $newTask;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->intervals = IntervalFactory::withRandomRelations()->createMany(self::INTERVALS_AMOUNT);

        $this->newTask = TaskFactory::create();
    }

    public function test_bulk_edit(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->newTask->id);

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseMissing('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervals->toArray()];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertSuccess();
        $response->assertJson(['updated' => $this->intervals->pluck('id')->toArray()]);
        $response->assertJsonMissing(['not_found']);

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        //TODO change later
    }

    public function test_with_not_existing_intervals(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->newTask->id);

        $nonIntervals = [
            ['id' => TimeInterval::max('id') + 1, 'task_id' => $this->newTask->id],
            ['id' => TimeInterval::max('id') + 2, 'task_id' => $this->newTask->id]
        ];

        $requestData = ['intervals' => array_merge($this->intervals->toArray(), $nonIntervals)];

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseMissing('time_intervals', $interval->toArray());
        }

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertJson(['updated' => TimeInterval::all()->pluck('id')->toArray()]);
        $response->assertJson(['not_found' => Arr::only($nonIntervals, 'id')]);

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        //TODO change later
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
