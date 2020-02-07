<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class CountTest
 */
class BulkRemoveTest extends TestCase
{
    private const URI = 'v1/time-intervals/bulk-remove';

    private const INTERVALS_AMOUNT = 5;

    /**
     * @var User
     */
    private $admin;

    /**
     * @var Collection
     */
    private $intervals;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->intervals = IntervalFactory::withRandomRelations()->createMany(self::INTERVALS_AMOUNT);
    }

    public function test_bulk_remove(): void
    {
        foreach ($this->intervals as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervals->pluck('id')->toArray()];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertSuccess();
        $response->assertJson(['removed' => $this->intervals->pluck('id')->toArray()]);

        $response->assertJsonMissing(['not_found']);

        foreach ($this->intervals as $interval) {
            $this->assertSoftDeleted('time_intervals', $interval->toArray());
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

        $response->assertStatus(self::HTTP_MULTI_STATUS);
        $response->assertJson(['removed' => TimeInterval::all()->pluck('id')->toArray()]);
        $response->assertJson(['not_found' => array_only($nonIntervals, 'id')]);

        foreach ($this->intervals as $interval) {
            $this->assertSoftDeleted('time_intervals', $interval->toArray());
        }
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
