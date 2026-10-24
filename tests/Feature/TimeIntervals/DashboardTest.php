<?php

namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    private const URI = '/api/report/dashboard';

    private const INTERVALS_AMOUNT = 2;

    private Collection $intervals;
    private User $admin;
    private array $requestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->intervals = IntervalFactory::forUser($this->admin)->createMany(self::INTERVALS_AMOUNT);

        $this->requestData = [
            'start_at' => Carbon::parse($this->intervals->min('start_at'))->startOfDay()->toDateTimeString(),
            'end_at' => Carbon::parse($this->intervals->max('end_at'))->endOfDay()->toDateTimeString(),
            'user_timezone' => 'UTC',
            'users' => [$this->admin->id],
            'projects' => $this->intervals
                ->map(static fn (TimeInterval $interval) => $interval->task->project_id)
                ->unique()
                ->values()
                ->all(),
        ];
    }

    public function test_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->requestData);

        $response->assertOk();

        $userIntervals = collect($response->json('data')[$this->admin->id]);

        $this->assertCount($this->intervals->count(), $userIntervals);
        $this->assertEqualsCanonicalizing(
            $this->intervals->pluck('id')->all(),
            $userIntervals->pluck('id')->all()
        );

        $expectedDuration = $this->intervals->sum(
            static fn (TimeInterval $interval) => (int) Carbon::parse($interval->end_at)
                ->diffInSeconds($interval->start_at, true)
        );
        $this->assertSame($expectedDuration, (int) $userIntervals->sum('durationAtSelectedPeriod'));
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI, $this->requestData);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertStatus(self::HTTP_UNPROCESSABLE_ENTITY);
    }
}
