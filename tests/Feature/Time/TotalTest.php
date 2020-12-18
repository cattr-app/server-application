<?php

namespace Tests\Feature\Time;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\Facades\IntervalFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class TotalTest extends TestCase
{
    private const URI = 'time/total';

    private const INTERVALS_AMOUNT = 10;

    private Collection $intervals;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->intervals = IntervalFactory::forUser($this->admin)->createMany(self::INTERVALS_AMOUNT);
    }

    public function test_total(): void
    {
        $requestData = [
            'start_at' => $this->intervals->min('start_at'),
            'end_at' => $this->intervals->max('end_at')->addMinute(),
            'user_id' => $this->admin->id
        ];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);
        $response->assertOk();

        $totalTime = $this->intervals->sum(static function ($interval) {
            return Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at);
        });

        $response->assertJson(['time' => $totalTime]);
        $response->assertJsonFragment(['start' => $this->intervals->min('start_at')]);
        $response->assertJsonFragment(['end' => $this->intervals->max('end_at')]);
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
