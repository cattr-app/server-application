<?php

namespace Tests\Feature\TimeUseReport;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = '/api/report/time';

    private const INTERVALS_AMOUNT = 10;

    private Collection $intervals;

    private User $admin;
    private int $duration = 0;
    private array $userIds;
    private array $requestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->intervals = IntervalFactory::withRandomRelations()->createMany(self::INTERVALS_AMOUNT);

        $this->intervals->each(function (TimeInterval $interval) {
            $this->userIds[] = $interval->user_id;
            $this->duration += (int) Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at, true);
        });

        $this->requestData = [
            'start_at' => Carbon::parse($this->intervals->min('start_at'))->startOfDay()->toDateTimeString(),
            'end_at' => Carbon::parse($this->intervals->max('end_at'))->endOfDay()->toDateTimeString(),
            'users' => array_values(array_unique($this->userIds)),
        ];
    }

    public function test_list(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->requestData);
        $response->assertOk();

        $report = collect($response->json('data'));

        $this->assertSame($this->duration, (int) $report->sum('time'));
        $this->assertEqualsCanonicalizing(
            array_unique($this->userIds),
            $report->pluck('user.id')->all()
        );
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
