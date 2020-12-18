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
    private const URI = 'time-use-report/list';

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
            $this->duration += Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at);
        });

        $this->requestData = [
            'start_at' => $this->intervals->min('start_at'),
            'end_at' => $this->intervals->max('end_at')->addMinute(),
            'user_ids' => $this->userIds
        ];
    }

    public function test_list(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->requestData);
        $response->assertOk();

        $totalTime = collect($response->json())->pluck('total_time')->sum();

        $this->assertEquals($this->duration, $totalTime);
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
